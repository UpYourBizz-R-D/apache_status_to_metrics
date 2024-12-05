#!/bin/bash

# Check if Apache is installed
if ! command -v apache2 &> /dev/null
then
    echo "Apache is not installed. Please install Apache and try again."
    exit 1
fi

# Check if PHP is installed
if ! command -v php &> /dev/null
then
    echo "PHP is not installed. Please install PHP and try again."
    exit 1
fi

# Install Apache modules
a2enmod status
a2enmod ext_filter

# Download script from GitHub
curl -o /var/www/status_to_metrics.php https://raw.githubusercontent.com/UpYourBizz-R-D/apache_status_to_metrics/refs/heads/main/status_to_metrics.php
chmod +x /var/www/status_to_metrics.php

# Modify Apache mod_status configuration
# Backup the original configuration file
cp /etc/apache2/mods-available/status.conf /etc/apache2/mods-available/status.conf.bak

# Accept IP addresses or CIDR from command line arguments
ip_list=("$@")

# Construct Require ip directives
require_ip=""
for ip in "${ip_list[@]}"; do
    require_ip+="Require ip $ip"$'\n\t'
done

# Replace the <Location /server-status> block in the mod_status configuration
new_block='#### Custom block for metrics ####
    
    Alias /server-status /usr/share/apache2/status

    ExtFilterDefine metrics_filter mode=output cmd="/var/www/status_to_metrics.php"
    
    <Location /server-status>
        SetHandler server-status
        SetOutputFilter metrics_filter
        Require local
        '"$require_ip"'
        #Require ip 192.0.2.0/24
    </Location>
#### END Custom block for metrics ####'

# Check if the custom block already exists
if grep -q "#### Custom block for metrics ####" /etc/apache2/mods-available/status.conf; then
    # Replace the existing custom block
    awk -v new_block="$new_block" '
    BEGIN { in_block = 0 }
    /#### Custom block for metrics ####/ { in_block = 1; print new_block; next }
    /#### END Custom block for metrics ####/ { in_block = 0; next }
    !in_block { print }
    ' /etc/apache2/mods-available/status.conf > /etc/apache2/mods-available/status.conf.tmp && mv /etc/apache2/mods-available/status.conf.tmp /etc/apache2/mods-available/status.conf
else
    # Replace the <Location /server-status> block
    awk -v new_block="$new_block" '
    BEGIN { in_block = 0 }
    /<Location \/server-status>/ { in_block = 1; print new_block; next }
    /<\/Location>/ { in_block = 0; next }
    !in_block { print }
    ' /etc/apache2/mods-available/status.conf > /etc/apache2/mods-available/status.conf.tmp && mv /etc/apache2/mods-available/status.conf.tmp /etc/apache2/mods-available/status.conf
fi


# Restart Apache to apply changes
apache2ctl configtest
if [ $? -eq 0 ]; then
    read -p "Config test is OK. Do you want to do a graceful restart? (y/n, default is n): " answer < /dev/tty
    answer=${answer:-n}
    if [ "$answer" = "y" ]; then
        apache2ctl graceful
        echo -e "\e[32mApache restarted successfully.\e[0m"
    else
        echo "Skipping graceful restart."
    fi
else
    echo -e "\e[31mConfig test failed. Please check the configuration.\e[0m"
    exit 1
fi

# apache_status_to_metrics
Convert status apache to prometheus metrics (via php script)

# concept
Rewrite the apache server status page to a prometheus metrics format, on the fly

- Enable mod_status, ext_filter
- add alias
- add output filter

```
  Alias /server-status /usr/share/apache2/status

	ExtFilterDefine metrics_filter mode=output cmd="/var/www/status_to_metrics.php"

  <Location /server-status>
		SetHandler server-status
		SetOutputFilter metrics_filter
		Require local
	
	</Location>
```

Possibility to add other Require to access from another ip (ex : prometheus)

# install auto avec curl

With no ip
```
curl -fsSL https://raw.githubusercontent.com/UpYourBizz-R-D/apache_status_to_metrics/refs/heads/main/install.sh | bash
```

With ips. Remplace ip1 .. by an ip or cidr
```
curl -fsSL https://raw.githubusercontent.com/UpYourBizz-R-D/apache_status_to_metrics/refs/heads/main/install.sh | bash -s -- ip1 ip2 ip3
```


# install auto avec wget

With no ip
```
wget -qO- https://raw.githubusercontent.com/UpYourBizz-R-D/apache_status_to_metrics/refs/heads/main/install.sh | bash
```

With ips. Remplace ip1 .. by an ip or cidr
```
wget -qO- https://raw.githubusercontent.com/UpYourBizz-R-D/apache_status_to_metrics/refs/heads/main/install.sh | bash -s -- ip1 ip2 ip3
```


# usage

path for prometheus
```
https://domain.com/server-status?auto=&metrics=
```

Without paramêters, serve the normal apache status page

With **auto** => serve a key=value file (normal feature)

With **auto+metrics** => serve a text file for prometheus


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

# install auto


# Wordpress Plugin: Some Redirects

A quick and drity way to add some custom redirects (301, 302, 410) to your site from the backend.

## Examples
Your Wordpress home URL is ```www.mysite.tld```. For each redirect rule add a new line to Tools → Weiterleitung. 

### 410 Gone
To say ```www.mysite.tld/old-image.jpg``` doesn't exist any longer add the line:
```
410 /old-image.jpg
```

### Redirects
To redirect from www.mysite.tld/old-page.html to www.mysite.tld/new-page/ add the line:
```
/old-page.html http://www.mysite.tld/new-page/

## Notices
Admin-Pages are not redirectable. 

@todo english version

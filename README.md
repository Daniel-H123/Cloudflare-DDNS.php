# Cloudflare-DDNS.php

Download update.php.

Change:
`CLOUDFLARE_API_KEY` => your cloudflare api key ([profile -> apikeys](https://dash.cloudflare.com/profile));
`CLOUDFLARE_EMAIL` => your cloudflare email;
`TOKEN` => Your random password (use not necessary but highly recommended).

Domains with multiple top level domains (like .co.uk) are currently not supported.

Example request url: https://website.com/update.php?email=example@email.com&token=VerySecureToken&domain=example.domain.com&ip=127.0.0.1

# Cloudflare Dynamic DNS Update Script
## Overview
This PHP script is designed to update the DNS record of a domain on Cloudflare dynamically. It provides a simple mechanism to keep your DNS records up to date with the current IP address of your server or device. It is particularly useful in combination with devices equipped with a built-in DynDNS updater, such as FritzBox routers or a Synology NAS.

## Hosted version
If you don't want to go through the hassle of hosting the script yourself, you can take advantage of the hosted version available for free. For the documentation, you can visit: [cloudflare-dyndns.com](https://cloudflare-dyndns.com/documentation).  This option allows you to enjoy the benefits of dynamic DNS updates without the need to set up and maintain your own server.

**Please note that while this hosted version is provided as a convenience, ensure that you consider the security implications of passing sensitive information through other servers.**

## How it Works
The script accepts parameters through a GET request, including the email address, token, domain, and IP address. It then authenticates the request, validates the IP address, and proceeds to update the DNS record on Cloudflare if necessary.

## Usage
To update the DNS record, make a GET request to the script with the following parameters:

email: Your Cloudflare account email.
token: A secure token for authentication.
domain: The domain for which the DNS record needs to be updated.
ip: The new IP address.
proxied (optional): Whether the record is being proxied. Defaults to true if not specified.
Example:

```
bash
https://website.com/update.php?email=example@email.com&token=VerySecureToken&domain=example.domain.com&ip=127.0.0.1
```

## Configuration
Before using the script, configure the following constants in the script:

TOKEN: Your custom password for authentication.
CLOUDFLARE_EMAIL: Your Cloudflare account email.
CLOUDFLARE_API_KEY: Your Cloudflare API key.

## Dependencies
This script uses the Cloudflare API to interact with your DNS records. Ensure that cURL is enabled on your server.

## Important Notes
Ensure that the script is secure and protected from unauthorized access.
Keep your custom token and Cloudflare credentials secure.
This script assumes that the DNS record is of type A.
Contributions
Feel free to contribute to the improvement of this script by submitting pull requests or reporting issues.

## Disclaimer
Use this script at your own risk. The script author and contributors are not responsible for any misuse or damage caused by its use.

## License
This script is released under the MIT License. See the [LICENSE](https://github.com/Daniel-H123/Cloudflare-DDNS.php/tree/master?tab=MIT-1-ov-file) file for more details.

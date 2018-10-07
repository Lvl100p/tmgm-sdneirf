# tmgm-sdneirf

### Required software (install if not already installed)

1. [PHP](http://php.net/downloads.php)
2. [Composer](https://getcomposer.org/download/)
3. [VirtualBox](https://www.virtualbox.org/wiki/Downloads)
4. [Vagrant](https://www.vagrantup.com/downloads.html)
5. [PowerShell 3.0 (if using Windows)](https://docs.microsoft.com/en-us/skypeforbusiness/set-up-your-computer-for-windows-powershell/download-and-install-windows-powershell-3-0)

### Installation instructions

1. First make sure that all required software is already installed.
2. Clone this repository to a folder of your choosing.
3. `cd` into the cloned repository folder.
4. Run `composer install`.
5. Run `vendor/bin/homestead make`.
6. Generate an SSH key/pair if you don't already have one. An easy way to do this is to use the following command:  
`ssh-keygen -t rsa -b 4096 -C "your_email@example.com"`
7. Edit `Homestead.yaml` and add the following to the `sites` property:
```
-
  map: friends-mgmt.test
  to: /home/vagrant/code/friends-mgmt/public
```
8. Check that the `authorize` and `keys` properties correctly specify the locations of your public and private key, respectively.
9. Run `vendor\\bin\\homestead make` (Windows) or `php vendor/bin/homestead make` (Mac/Linux).
10. Run `vagrant up`. This may take a while if it's the first time you're running this command.  
If the host machine is Windows, and you are unable to boot into the virtual machine, you may need to enable hardware virtualization via the BIOS.
11. Run `vagrant ssh` to login to the guest.
12. `cd` into `/code/friends-mgmt` and run `composer install`.
13. Rename `.env.example` to `.env` and run `php artisan key:generate`.
14. Check that the `storage/` and `bootstrap/cache/` folders have write permissions for group, user, and others:  
`ls -ld storage` and `ls -ld bootstrap/cache`.  
If not, run the following commands:
```
sudo chmod -R guo+w storage/
sudo chmod -R guo+w bootstrap/cache/
```
15. Run `exit` to logout from the guest.
16. Edit `C:\Windows\System32\drivers\etc\hosts` (with administrator permissions) and add the following:  
`192.168.10.10 friends-mgmt.test`.  
On Mac and Linux, the file is located at `/etc/hosts`.
17. Check that `http://friends-mgmt.test` is accessible. If not, run `vagrant up --provision` and retry.

### API

For all the listed APIs, the server will return a 400 response if the JSON request is invalid, e.g. missing required keys, invalid email string, wrong data types. If the request includes additional keys that are not understood by the server, they will be ignored.

**POST /api/v1/befriend**  
Accept: application/json  
Content-Type: application/json  
*Create a friend connection between the two emails specified in the JSON request.*  

Proposed failure responses:
- Both emails are identical
```
{
  "success": false
  "reason": "same email"
}
```
- At least one email is unregistered
```
{
  "success": false
  "reason": "unregistered email"
}
```
- Both emails are already friends
```
{
  "success": false
  "reason": "already friends"
}
```

**GET /api/v1/friends**  
Accept: application/json  
*Retrieve the friends email list for the email specified in the JSON request.*  

Proposed failure responses:
- Unregistered email
```
{
  "success": false
  "reason": "unregistered email"
}
```

**GET /api/v1/friends-common**  
Accept: application/json  
*Retrieve the common friends email list between both emails specified in the JSON request.*  

Proposed failure responses:
- Both emails are identical
```
{
  "success": false
  "reason": "same email"
}
```
- At least one email is unregistered
```
{
  "success": false
  "reason": "unregistered email"
}
```

**POST /api/v1/subscribe**  
Accept: application/json  
Content-Type: application/json  
*Subscribe the requestor email to the target email, both of which are specified in the JSON request.*  

Proposed failure responses:
- Both emails are identical
```
{
  "success": false
  "reason": "same email"
}
```
- At least one email is unregistered
```
{
  "success": false
  "reason": "unregistered email"
}
```
- Requestor email is already subscribed to target email
```
{
  "success": false
  "reason": "already subscribed"
}
```

**POST /api/v1/block**  
Accept: application/json  
Content-Type: application/json  
*Stop the requestor email from receiving updates from the target email, both of which are specified in the JSON request.*  

Proposed failure responses:
- Both emails are identical
```
{
  "success": false
  "reason": "same email"
}
```
- At least one email is unregistered
```
{
  "success": false
  "reason": "unregistered email"
}
```
- Requestor email has already blocked target email
```
{
  "success": false
  "reason": "already blocked"
}
```

**GET /api/v1/recipients**  
Accept: application/json  
*Retrieve the list of emails which can receive updates from the sender email specified in the JSON request.*  

Proposed failure responses:
- Sender email is unregistered
```
{
  "success": false
  "reason": "unregistered email"
}
```

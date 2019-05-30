# Wordpress sandbox

## Install

- configure a localhost pointing to http://bluecoding-wp-bedrock.local
- if you need the Facebook Auth plugin you might need to setup a https certificate in local machine
- `git clone`
- `composer install`
- import db file: [resources/db.sql](resources/db.sql)
- copy [.env.example](.env.example) to a new file called `.env` and fill the DB credentials and `WP_HOME=http://bluecoding-wp-bedrock.local`
- the user:password is: `admin:jiPPoVBkez%51^SV7c`, but you can change that quite easily with wp-cli


## Facebook Auth

If you need this plugin then you will need a https certificate in order to use it since facebook requires this feature.

The list of administrators are in the [web/app/plugins/scodx-fb-auth/scodx-fb-auth.php](web/app/plugins/scodx-fb-auth/scodx-fb-auth.php) at line 16:

```
/**
 *  Define the users that initially are going to be
 *  admin in this array
 */
define('FB_ADMIN', array(
  array(
    'name' => 'Oscar Sanchez',
    'email' => 'oscar.exe@gmail.com',
  ),
));
```
Edit this array in order to include yourself as a wp admin

Currently there is no frontend way to edit the admins, but these are stored in the table `scodx_fb_auth_admins` which you can edit with any mysql client.

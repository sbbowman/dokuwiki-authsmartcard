dokuwiki-authsmartcard
======================

Dokuwiki plugin providing client certificate (smartcard) authentication.

# Summary

This plugin authenticates users to dokuwiki by comparing the user's client certificate's CN attribute with the group field for a user in the dokuwiki user database.

For example, user John Doe needs access to the Wiki.  John has a client certificate (or smartcard with a certificate on it) that as a CN attribute of 'DOE.JOHN.99999'.  John Doe has is added to the dokuwiki user database by an administrator.  His username is 'jdoe'.  His groups are set to 'DOE.JOHN.99999, finance'.

When John Doe access the Wiki with his browser configured to use a client certificate (or smartcard), this plugin will authenticate user 'jdoe' by comparing the CN of his certificate with the groups he is a member of in the dokuwiki user database.  

This is a rewrite of an old plugin by Margus Pärt (mxrguspxrt).  Much of the plugin structure and API changed with dokuwiki requiring a rewrite.

# Prerequisites

## Apache Configuration

In addition to all the other SSL directives (SSLCertificateFile, SSLCertificateKeyFile, SSLCACertificateFile, etc.) you'll need to require client certificates:

```
    SSLVerifyClient require
    SSLVerifyDepth 10
    SSLOptions +StdEnvVars +ExportCertData
```

Depending on your version of Apache and virtual host configuration, you may also need (but should use carefully):

```
    SSLInsecureRenegotiation on
```

You will also need to allow htaccess for the virtual directory that contains the plugin.  Otherwise, integrate the entries in authsmartcard/.htaccess into your Apache configuration specific for that virtual directory.

Redirect requests to the authentication plugin, so that requests to the first page of the wiki, e.g., https://YOUR_DOMAIN/DOKUWIKI_PATH/, are automatically authenticated.

```
    RedirectMatch ^/$ https://YOUR_DOMAIN/DOKUWIKI_PATH/lib/plugins/authsmartcard/auth/
```

If you don't do the above step, you'll need to edit your main wiki login page (YOUR_DOKUWIKI_INSTALLATION/inc/lang/YOUR_CHOSEN_LANGUAGE/login.txt) to have a link for users to authenticate themselves to the wiki.  Something like:

To log on with your client certificate, follow this link: [[lib/plugins/authsmartcard/auth/|Authenticate with Certificate/Smartcard]]

# Installation

## Automatically

You can install this by providing the URL to your Dokuwiki's Plugin Manager - https://github.com/sbbowman/dokuwiki-authsmartcard/zipball/master

## Manually

Unpack the plugin to DOKUWIKI_ROOT/lib/plugins/

Ensure that DOKUWIKI_ROOT/lib/plugins/authsmartcard/* is readable by Apache.

# Configuration

Ensure that the authtype is set to authsmartcard in conf/local.php or conf/local.protected.php:

```
$conf['authtype'] = 'authsmartcard';
```

Available configuration options for the plugin are:

```
// Enable logging?
$conf['log_to_file']		= true;
// If log_to_file is enabled, where to log?  Make sure apache/php can write to this file
$conf['logfile']		= "/full/path/to/logfile/writable/by/apache";
```

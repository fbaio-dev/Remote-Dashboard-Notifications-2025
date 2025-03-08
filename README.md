Remote Dashboard Notifications
==============================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ThemeAvenue/Remote-Dashboard-Notifications/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/ThemeAvenue/Remote-Dashboard-Notifications/?branch=develop)

Developers, have you ever wanted to ask something to your users? Tried to get some feedback about your product? Want them to leave a comment on your site? This plugin will help you do that. 

Remote Dashboard Notifications (RDN) aka admin notice is a plugin made for themes and plugins developers who want to send short notifications to their users. This plugin will allow a theme / plugin author to display a notice in the client's admin dashboard using the WordPress admin notices.

<img src="http://i.imgur.com/hhMz2J7.jpg" width="700" />

The plugin works on a server / client basis. The product author uses a WordPress install as the server (where the plugin has to be installed), and the user's WordPress site will be the client.

## How it works

The plugin is meant to manage messages for multiple products. We call _channels_ the different types of notifications. For instance, if I have 2 products, Product A and Product B, I will create 2 channels in the server: Channel A and Channel B.

Once a channel is created, the plugin will create an ID and a key used to authenticate the client requests. When integrating RDN to a theme or plugin, the client class will be instanciated with the desired channel ID and key.

When a client site will check for new notifications, it will make an HTTP request (using WordPress HTTP API) to the server. If the requested channel exists and the key is valid, the server will return the latest notification (if any) in a JSON encoded array.

The client site will then cache this response and display it as an admin notice in the WordPress site dashboard until the user dismisses it.

## Integration in a theme or plugin

#### Prerequisite

The following has to be understood before you can integrate this feature in your product:

* **Server**: the WordPress site where the plugin is installed
* **Client**: the WordPress site where the class in instantiated (through a theme or a plugin)

### Integration steps

It is really easy to integrate this feature in a plugin or theme. Only four steps are required:

1. Add this [Client](https://github.com/Niloys7/remote-admin-notification-client) to the theme / plugin directory 
2. Create a new channel on the server
3. Get the channel ID & key (in the term edit screen)
4. Register the notification on the client with the server's URL (`http://domain.com`), the channel ID and key

### Integration examples

#### Theme

Place this into `functions.php`.

    require( 'class-remote-notification-client.php' );

    if ( function_exists( 'wpi_rdnc_add_notification' ) ) {
        wpi_rdnc_add_notification( 35, 'f76714a0a97d1186', 'http://server.url' );
    }

## Privacy

The plugin does not collect any information about the client site. The server plugin is completely passive and its only job is to return messages to the requestor.

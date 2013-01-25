Plant
=====

Plant is a lightweight MVC framework for PHP. My aim was to make building a site fun again, and there are many
tools included to take a lot of the mundane aspects of web development out of your hands. Plant is primarily aimed
at experienced web developers looking for a faster way to produce reliable and flexible sites with minimal hassle.

Requirements
------------

* PHP 5.2 or higher
* MySQL
* mod_rewrite or similiar URL rewriting plugin

Installing
----------

1. Run `git clone https://github.com/foxxyz/Plant` for a fresh copy.
2. Modify `config.local.inc.php` in the `app/config/` directory and fill in the details of your database connection.
3. Set write permissions on the following directories so Plant can write and access data:
	* `/app/controllers/`
	* `/app/templates/`
	* `/core/cache/`
	* `/content/`
4. Load `/install/` on your website. Follow the steps, and note the generated password at the end.
5. Delete the `/install/` directory on your server.

That's it! The installation script will send you to the Site Admin where you'll be able to change your password and start constructing your site.

FAQ
---

### I'm getting Database warnings during the installation?

Make sure your DB user has database creation privileges. If not, create the new database yourself (make sure the name
matches the setting in your `config.local.inc.php` file) and run the install script again.

### Need more help?

Hit me up: http://codedealers.com/contact/

Now scram.

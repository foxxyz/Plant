**⚠️ Plant is past end-of-life status and is no longer under development. Feel free to use this software for any purpose but I no longer provide any support for it.**

Plant
=====

Plant ~~is~~ was a lightweight MVC framework for PHP. My aim was to make building a site fun again, and there are many
tools included to take a lot of the mundane aspects of web development out of your hands. Plant is primarily aimed
at experienced web developers looking for a faster way to produce reliable and flexible sites with minimal hassle.

Requirements
------------

* PHP 5.2 or higher
* MySQL
* mod_rewrite for nice URLs

Installing
----------

1. Run `git clone https://github.com/foxxyz/Plant` for a fresh copy.
2. Modify `config.local.inc.php` in the `app/config/` directory and fill in the details of your database connection.
3. Set write permissions on the following files & directories so Plant can write and access data:
	* `/app/controllers/`
	* `/app/templates/`
	* `/core/cache/`
	* `/plugins/logger/error.log`
4. Load `/install/` on your website. Follow the steps, and note the generated password at the end.
5. Delete the `/install/` directory on your server.

The installation script will send you to the Site Admin where you'll be able to change your password and start constructing your site.

FAQ
---

### Now I have a "Plant" subdirectory on my server? How do I get it in the root?

To clone Plant into the current directory, run `git clone https://github.com/foxxyz/Plant .` instead of the command above (just make sure the directory is empty).

### How do I set write permissions on those directories?

Assuming your web server runs as a separate user, set your site files to that user's group and add group write permissions:

`chmod g+w app/controllers/ app/templates/ core/cache/ plugins/logger/error.log`

### I'm getting Database warnings during the installation?

Make sure your DB user has database creation privileges. If not, create the new database yourself (make sure the name
matches the setting in your `config.local.inc.php` file) and run the install script again.

License
------------------------
Plant is copyright 2007 Ivo Janssen and released under the GNU General Public License version 3 (the "License"). You may not use this work except in compliance with the License. You may obtain a copy of the License in the LICENSE file, or at http://www.gnu.org/licenses/gpl-3.0.txt

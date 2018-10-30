# ed-lti

This plugin allows Virtual Learning Environment (VLE) users to create blogs on a WordPress multisite installation through the use of Learning Tools Interoperability (LTI) tools. In this case, the LTI Consumer is the VLE (e.g. Moodle) and WordPress is the LTI Provider. VLE roles are mapped to appropriate WordPress roles in all cases.

The plugin supports the creation of two blog types: course blogs and student blogs.

## Course Blogs

The first VLE user to click on a course blog tool link will be taken to the WordPress multisite install and a blog will be created for the course. The user will also be made a member of the blog. Any subsequent users that click on the link are added to the blog as a member. In most cases, staff (for example, a user with a teacher role) will be given the WordPress administrator role and students the WordPress author role. 

## Student Blogs

If a VLE user with the student role clicks on a student blog tool link they are taken to the WordPress multisite install and a blog is created for them. The student is also added to the blog as an administrator.  In most cases, if a member staff (for example, a user with the teacher role) clicks the student tool link, they are taken to a WordPress page with a list of links to the student blogs associated with the course. If the staff member clicks on one of the links, they are taken to the home page of the blog and made a blog author (WordPress role).

## Associated Plugins  
  
This plugin works with the following plugins (none of which are mandatory):

- NS Cloner - Site Copier
- Multisite Privacy
- More Privacy Options

If NS Cloner is installed and activated. NS Cloner will be used to create blogs instead of WordPress Core. 

If the Multisite Privacy plugin or the More Privacy Option plugins are installed, Wordpress admin have the option of making new blogs private on creation. If neither of these plugins are installed, blogs will always be made public on creation.  

## Requirements

The following versions of PHP are supported:

- PHP 7.0
- PHP 7.1
- PHP 7.2

The following versions of WordPress are supported.

 - WordPress 4.9.8
 
Note: It is very possible that this plugin will work with earlier versions of WordPress, but it has only been tested on the above.

## Installation 

Copy the ed-lti folder to the plugins folder.

Activate the plugin on the plugins page in WordPress admin (Network Admin -> Plugins). 

Once this is done, you should see two new menu items in the network admin settings screen (Network Admin -> Settings) : LTI Consumer Keys and LTI Settings.

Add a consumer key for your VLE(s) (Network Admin -> Settings -> LTI Consumer Keys). This includes entering a name, key and password. You should also check the enabled checkbox to activate your key. Make a note of the key and password, as they will be needed when you create an LTI tool in your VLE.

Make sure your LTI Settings are correct  (Network Admin -> Settings -> LTI Settings). On this page, you can enter the url of the blog that you would like to use as a template for new blogs. This value defaults to the root blog if left empty. If one of the privacy plugins is installed, you have the option of making blogs private on site creation. You can also enter the URL for a help page that is included in error messages. If this field is left empty, error messages will not include help info.

Note: for instructions on how to install a plugin manually, please visit [WordPress for dummies](https://www.dummies.com/web-design-development/wordpress/templates-themes-plugins/how-to-install-wordpress-plugins-manually/)

### VLE Settings

After you have installed and configured the plugin in WordPress, you will need to create LTI tools in your VLE. You should create separate tools for student and course blogs. You can find more info about creating LTI tools on Moodle and Learn on their sites:

[Creating an LTI tool on Moodle](https://docs.moodle.org/35/en/External_tool)

[Creating an LTI tool on Learn](https://help.blackboard.com/Learn/Administrator/SaaS/Integrations/Learning_Tools_Interoperability#add-a-new-lti-tool-provider_OTP-3)
 
You should name your tool appropriately; e.g, WordPress Course, WordPress Student.

The tool URL is the URL of your WordPress install; e.g., https://<mydomain.com>/index.php. **Important**: you must include index.php after your domain or the link won't work.

You should include the consumer key and password that you created in WordPress admin. You should use the same consumer key password pair for both course and student LTI tools. Although, it is recommend that you create a separate key password pair (in WordPress) for each VLE that you use; i.e. use one key password pair for Moodle and another for Learn. 

For course tools, you do not need to add any custom parameters. For student tools, you should add the following custom parameter:

    blog_type=student

You will also probably want to set your tool to launch in a new window, although this is not mandatory.

## Further Information

It is possible to create a blog for groups; i.e., a blog that is restricted to group members only. Simply add the preconfigured course LTI tool to the group in question. 
 
If you add a second course tool link to a course, when the first user clicks on the link a second blog will be created for the course, which will include appropriate version info. For example, the title of the blog will be *My Course Blog 2*. Each new link will result in an additional blog for the course. This is also true for student blogs, so it is possible for students to have multiple individual blogs for a course.

## Changelog

See the [project changelog](https://github.com/uoe-dlam/ed-lti/blob/master/CHANGELOG.md)

## Support

Bugs and feature requests are tracked on  [GitHub](https://github.com/uoe-dlam/ed-lti/issues).

If you have any questions about ed-lti  _please_  open a ticket here; please  **don't**  email the address below.

## License

This package is released under the MIT License. See the bundled  [LICENSE](https://github.com/uoe-dlam/ed-lti/blob/master/LICENSE)  file for details.

## Credits

This code is principally developed and maintained by the University of Edinburgh's Digital Learning Applications and Media team .

This plugin was inspired by the IMS Basic Learning Tools Interoperability plugin (developed by Chuck Severance & Antoni Bertran). Some of the code used in this plugin is borrowed from that plugin. For further details, please visit [IMS Global](https://www.imsglobal.org/compliance/lti-plugin-wordpress-v33x)

This plugin also makes use of the PHP LTI Tool Provider Library developed by IMS Global. For further details, please visit  [GitHub](https://github.com/IMSGlobal/LTI-Tool-Provider-Library-PHP)




















# ed-lti

This plugin allows Virtual Learning Environment (VLE) users to create blogs on a WordPress multisite installation through the use of Learning Tools Interoperability (LTI) tools. The plugin is designed to make it easy to  integrate WordPress with a VLE course to provide an individual or group working space for students. It isn't designed to support course materials on an external WordPress site being included in the VLE.

In this case, the LTI Consumer is the VLE (e.g. Moodle) and WordPress is the LTI Provider. VLE roles are mapped to appropriate WordPress roles in all cases.

This is a single plugin that supports 2 different use cases:

1.	**Course Blog:** A single blog that is shared by all students in a course. 
2.	**Student Blog:** A single blog for every student in a course. 

Differentiating these two different uses of blogs is done as part of setting up the LTI tool in your VLE of choice. You set up the LTI tool at system level twice in the VLE, giving each instance a suitably descriptive name, and including custom parameters that tells the plugin in WordPress which behaviour to provide. 

Teachers are then able to drop an instance of the tool into their course wherever it’s required. 

### Course Blogs

The first VLE user to click on a course blog tool link will be taken to the WordPress multisite install and a blog will be created for the course. The user will also be made a member of the blog. Any subsequent users that click on the link are added to the blog as a member. Teachers on the course will get the WordPress Administrator role and students will be added as WordPress Authors.


### Student Blogs

If a VLE user with the student role clicks on a student blog tool link they are taken to the WordPress multisite install and a blog is created for them. The student is added to the blog with the Wordpress Administrator role.  Teachers on the course who follow the student tool link will be taken to a WordPress page with a list of links to all the student blogs associated with the course. If the Teacher clicks on one of the links, they are given the WordPress Author role and taken to the home page of the blog.


## Associated Plugins  
  
This plugin works with the following plugins (none of which are mandatory):

- NS Cloner - Site Copier
- Multisite Privacy
- More Privacy Options

If NS Cloner is installed and activated. NS Cloner will be used to create blogs instead of WordPress Core. 

If the Multisite Privacy plugin or the More Privacy Option plugins are installed, Wordpress admin have the option of making new blogs private on creation. If neither of these plugins are installed, blogs will always be made public on creation.  

## Installation and Configuration

### WordPress  

1. Copy the ed-lti folder to the plugins folder.
**Note:** for instructions on how to install a plugin manually, please visit [WordPress for dummies](https://www.dummies.com/web-design-development/wordpress/templates-themes-plugins/how-to-install-wordpress-plugins-manually/)
2. From **Network Admin > Plugins** select the plugin and click **Network Activate**.
3. Go to **Network Admin > Settings > LTI Consumer Keys** and enter a name, key and password. Make a note of the key and password, as they will be needed when you create an LTI tool in your VLE.
4. Check **Enabled** to activate your key.
5. Go to **Network Admin > Settings > LTI Settings** and enter the URL of the blog that you would like to use as the template for new blogs.  This value defaults to the root blog if left empty. 
6. If one of the privacy plugins is installed, you have the option of making blogs private on site creation. You can also enter the URL for a help page that is included in error messages. If this field is left empty, error messages will not include help info.


### VLE Settings

After you have installed and configured the plugin in WordPress, you will need to create LTI tools in your VLE. You should create separate tools for student and course blogs. You can find more info about creating LTI tools on Moodle and Learn on their sites:

* [Creating an LTI tool on Moodle](https://docs.moodle.org/35/en/External_tool)

* [Creating an LTI tool on Learn](https://help.blackboard.com/Learn/Administrator/SaaS/Integrations/Learning_Tools_Interoperability#add-a-new-lti-tool-provider_OTP-3)
 
You should name your tool appropriately; e.g, WordPress Course, WordPress Student.

The tool URL is the URL of your WordPress install; e.g., https://<mydomain.com>/index.php. 
**Important**: you must include index.php after your domain or the link won't work.

You should include the consumer key and password that you created in WordPress admin. You should use the same consumer key password pair for both course and student LTI tool configurations. If you use more than one VLE, it is recommended that you create a separate key password pair (in WordPress) for each VLE that you use; i.e. use one key password pair for Moodle and another for Learn. 

For course tools, you do not need to add any custom parameters. 
For student tools, you must include the following custom parameter:

    blog_type=student

You will also probably want to set your tool to launch in a new window, although this is not mandatory.

## Further Information

### Multiple Blogs in a Course
It’s possible to have more than one instance of the tool per course, which could be useful for the Course Blogs behaviour above where you might want to have a few different activities within a course, each of which are supported by a WordPress blog.
 
If you add a second course tool link to a course, when the first user clicks on the link a second blog will be created for the course, which will include appropriate version info. For example, the title of the blog will be *My Course Blog 2*. Each new link will result in an additional blog for the course. This is also true for student blogs, so it is possible for students to have multiple individual blogs for a course.

### Sub-Course Group Blogs
If you are using groups within your VLE to limit access to a series of resources that support a learning activity, you can drop an instance of the blog tool into each group and the VLE permissions that restrict access to the group will take care of making sure that only the students in that group can get onto the blog. Probably the Course Blog behaviour is the one that’s most useful here too.

## Requirements

The following versions of PHP are supported:

- PHP 7.0
- PHP 7.1
- PHP 7.2

The following versions of WordPress are supported.

 - WordPress 4.9.8
 
Note: It is very possible that this plugin will work with earlier versions of WordPress, but it has only been tested on the above.

## Changelog

See the [project changelog](https://github.com/uoe-dlam/ed-lti/blob/master/CHANGELOG.md)

## Support

Bugs and feature requests are tracked on  [GitHub](https://github.com/uoe-dlam/ed-lti/issues).

If you have any questions about ed-lti  _please_  open a ticket here; please  **don't**  email the address below.

## License

This package is released under the GNU License. See the bundled  [LICENSE](https://github.com/uoe-dlam/ed-lti/blob/master/LICENSE)  file for details.

## Credits

This code is principally developed and maintained by the University of Edinburgh's Digital Learning Applications and Media team .

This plugin was inspired by the IMS Basic Learning Tools Interoperability plugin (developed by Chuck Severance & Antoni Bertran). Some of the code used in this plugin is borrowed from that plugin. For further details, please visit [IMS Global](https://www.imsglobal.org/compliance/lti-plugin-wordpress-v33x)

This plugin also makes use of the PHP LTI Tool Provider Library developed by IMS Global. For further details, please visit  [GitHub](https://github.com/IMSGlobal/LTI-Tool-Provider-Library-PHP)




















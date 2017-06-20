=== WP-Filebase Download Manager ===
Contributors: fabifott
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=wpfilebase%40fabi%2eme&item_name=WP-Filebase&no_shipping=0&no_note=1&tax=0&currency_code=USD&lc=US&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: filebase, filemanager, file, files, manager, upload, download, downloads, downloadmanager, images, pdf, widget, filelist, list, thumbnails, thumbnail, attachment, attachments, category, categories, media, template, ftp, http, mp3, id3
Requires at least: 3.1
Tested up to: 4.8
Stable tag: 0.3.4.24
Demo link: http://demo.wpfilebase.com/


Adds a powerful download manager including file categories, downloads counter, widgets, sorted file lists and more to your WordPress blog.

== Description ==

WP-Filebase is an advanced file download manager for WordPress.
It keeps Files structured in Categories, offers a Template System to create sortable, paginated File Lists and can sideload Files from other websites.
The Plugin is made for easy management of many Files and consistent output using Templates.

With WP-Filebase you can...

*	create dynamic paginated and sortable file lists
*	restrict downloads for certain user roles
*	build photo galleries
*	embed flash (or other formats) videos using a template player
*	easily publish MP3 and other audio files with automatic ID3 tag detection
*	allow users to upload files from the front-end

More features are:

*	Category / child category / file taxonomy
*	Automatic thumbnails
*	Built-in download counter
*	Drag and Drop file upload
*	File List Widget
*	Ajax file tree browser
*	Reads ID3 Tags and other file info of the most common file types (JPEG, videos etc...)
*	Customizable template system
*	Insert flexible shortcodes with the Visual Editor Plugin
*	Sortable paginated file lists
*	Supports [DataTables](http://datatables.net/) for dynamic lists
*	Associate files to posts and automatically attach them to the content
*	User Role access restrictions, limit file access to certain user roles 
*	Upload files in your browser, with FTP or from URL (sideloading)
*	Traffic limits and bandwidth throttle
*	Permalink structure
*	Hotlinking protection
*	Range download (allows users to pause downloads and continue them later)
*	Custom JavaScript download tracking (e.g. Google Analytics)
*	Many file properties like author, version, supported languages, platforms and license
*	Search integration
*	Automatic synchronization of file system and database

You can see a [live demo on my Website](http://fabi.me/downloads/ "WP-Filebase demo"), download manager [documentation can be found here](https://wpfilebase.com/documentation/ "WP-Filebase documentation").
For support, please [leave a message on my blog](http://fabi.me/wordpress-plugins/wp-filebase-file-download-manager/#postcomment "Post comment"). When having trouble don't forget to post PHP and Wordpress version! Any ideas/feature requests are welcome.

[WP-Filebase on GitHub](https://github.com/f4bsch/WP-Filebase).

= WP-Filebase Pro =
[WP-Filebase Pro](https://wpfilebase.com/) includes even more advanced features:

*	PDF indexing and thumbnails
*	File Pages with custom post type
*	Secondary Categories
*	Extended Permissions
*	File Passwords
*	Embedded front-end Upload Forms
*	Improved Syncing algorithm
*	Dropbox, Google Drive, Amazon S3 and FTP sync
*	XML Sitemap

== Installation ==

The usual way:
1. Upload the `wp-filebase` folder with all it's files to `wp-content/plugins`
2. Activate the Plugin

If you get an error message saying that the upload directory is not writable create the directory `/wp-content/uploads/filebase` and make it writable (FTP command: `CHMOD 777 wp-content/uploads/filebase`) for the webserver.

If you run nginx, add this to your config file to prevent direct file access:
`
location /wp-content/uploads/filebase {
	deny all;
	return 403;
}
`

Read more in [WP-Filebase documentation](https://wpfilebase.com/documentation/setup/).

== Frequently Asked Questions ==

= How can do I get the AJAX tree view like on http://fabi.me/downloads/ ? =

This feature is called File Browser. Go to WP-Filebase settings and click on the tab 'File Browser'. There you can select a post or page where the tree view shoud appear.

= How do I insert a file list into a post?  =

In the post editor click on the *WP-Filebase* button. In the appearing box click on *File list*, then select a category. Optionally you can select a custom template.

= How do I list a categories, sub categories and files?  =

To list all categories and files on your blog, create an empty page (e.g named *Downloads*). Then goto *WP-Filebase Settings* and select it in the post browser for the option *Post ID of the file browser*.
Now a file browser should be appended to the content of the page.

= How do I add files with FTP? =

Upload all files you want to add to the WP-Filebase upload directory (default is `wp-content/uploads/filebase`) with your FTP client. Then goto WP-Admin -> Tools -> WP-Filebase and click *Sync Filebase*. All your uploaded files are added to the database now. Categories are created automatically if files are in sub folders.

= How do I customize the appearance of filelists and attached files? =

You can change the HTML template under WP-Filebase -> Settings. To edit the stylesheet goto WP-Admin -> Tools -> WP-Filebase and click *Edit Stylesheet*.
Since Version 0.1.2.0 you can create your custom templates for individual file lists. You can manage the templates under WP-Admin -> Tools -> WP-Filebase -> Manage templates. When adding a tag to a post/page you can now select the template.

= How can I use custom file type/extension icons? =

WP-Filebase uses WordPress' default file type icons in `wp-includes/images/crystal` for files without a thumbnail. To use custom icons copy the icon files in PNG format named like `pdf.png` or `audio.png` to `wp-content/images/fileicons` (you have to create that folder first).

= What to do when downloading files does not work? =

Goto WP-Filebase Settings and disable Permalinks under "Download". Try to disable other plugins. Disable WP_CACHE. Enable WP_DEBUG to get more info about possible errors.

== Screenshots ==
1. The form to upload files
2. AJAX file tree view
3. Example of an embedded download box with the default template
4. The Editor Button to insert tags for filelists and download urls
5. The Editor Plugin to create shortcodes for files, categories and lists
6. The WP-Filebase Widgets


== Changelog ==

= 0.3.4.24 =
* New dashboard
* Fixed XSS vulnerability
* Fixed a memory leak when generating thumbnails
* Fixed inline upload permission issue
* Fixed drag&drop issues

= 3.4.23 =
* Fixed inline upload permission issue
* Fixed drag&drop issues
* Fixed a memory leak when generating thumbnails
* Fixed XSS vulnerability

= 3.4.22 =
* Rename field now visible when adding files
* Search widget now has placeholder
* %file_tags% now generates a list of tags with links
* Disable file pages with constant `WPFILEBASE_DISABLE_FILE_PAGES`
* Using Imagick for bmp thumbnails
* Prevent reporting PHP strict warnings and notices
* Fixed permissions issue for guests in `GetPermissionWhere`
* Fixed multiple uploaders on single page
* Fixed cloud sync caching bug
* Fixed FacetWP support bug

= 3.4.21 =
* Renamed WP-Filebase dashboard menu entry to `Dashboard`
* Developers: new filter `wpfilebase_ajax_public_actions`
* Auto-delete category ZIP files from `.tmp` folder
* Removed trailing `.0` in file size format for <1000 B
* Fixed incompatibility with the Divi-Builder plugin

= 3.4.19 =
* Fixed file browser category and file movement (drag&drop)
* Fixed upload widget permission issue
* Now capturing fatal PHP errors in the logs

= 3.4.18 =
* Improved handling of remote URLs of cloud files
* WP-Filebase Pro now auto-activates on domain name change (if License slots are available)

= 3.4.17 =
* New feature: change owner of file
* New template variable `%file_url_no_preview%`
* Fixed file browser delete button feedback (files no disappear after deletion)
* Fixed missing thumbnails after sync when custom thumbnail path is set
* Fixed CloudSync not scanning files with file preview enabled
* Fixed embedded video template for cloud files with enabled file preview
* Added cloud sync system tests

= 3.4.15 =
* Added support for Easy Digital Downloads integration

= 3.4.14 =
* Added column for deletion handling in Cloud Sync dashboard
* Fixed a front-end upload issue setting the wrong category
* Fixed FacetWP support

= 3.4.13 =
* Fixed FacetWP support: filter files without access permission

= 3.4.11 =
* Fixed cloud sync bug caused the file tree to be flattened
* FacetWP support: filter files without access permission
* Fixed file browser uploading to root category
* Fixed authentication issue for thumbnails

= 3.4.10 =
* Filepages now adopt file tags (e.g. for tag clouds)
* Fixed disable state detection for `exec`
* Verbose RPC test
* Automatic cloud Sync cache flush
* Fixed admin dashboard columns layout
* Removed deprecated reference to global `$user_ID`

= 3.4.9 =
* Prevent file rename for cloud-hosted files
* Fixed cloud sync file URL retrieval
* New template variables `%button_edit%`, `%button_delete%`

= 3.4.8 =
* New file batch action: delete thumbnails
* Improved error handling when generating cloud links
* Fixed WP post attachment images
* Fixed thumbnail creation error handling, https://github.com/f4bsch/WP-Filebase/pull/42
* Fixed cloud sync browser
* Fixed editor plugin menu bar


= 3.4.6 =
* Added document indexing hook for extensions
* Fixed error when editing categories
* Fixed date display in backend file list
* Fixed bug with small thumbnails
* Fixed encoding issue for keywords
* Fixed Cloud Sync browser for WebDav
* Fixed download count when behind a proxy (e.g. Cloudflare)
* Fixed broken thumbnails handling during rescan

= 3.4.4 =
* Fixed jQuery treeview compatibility issue
* Sync: improved thumbnail handling (stop thumbnails from being added as files)

= 3.4.3 =
* New Dashboard
* New upload box -- More responsive, new coloring adapts to admin theme
* Added logging system
* Added GitHub file name format version recognition
* Fills out file display and version automatically
* Fixed cron bug
* Updated image-picker and jquery-deserialize
* Added download URLs to backend file browser
* Removed file browser warning if not set
* Template variable file_name uses file_name_orignal if set
* Disabled output buffering for NGINX on progress reporting
* Fixed defaults for custom fields
* Combined & minified treeview scripts
* Fixed thumbnail detection
* Fixed AJAX for sites with semi-HTTPS (backend-only)
* Admin colors in file form
* Added better thumbnail preview after upload
* Fixed responsiveness of batch uploader
* Set Default Thumbnail size to 300px
* Fixed error `class getid3_lib not found`
* Changed thumbnail file name pattern: `X._[key].thumb.(jpg|png)` -- This prevents thumbnails from being added as actual files when meta data is lost (on site migration)
* Changed JS registration `jquery-treeview` to `wpfb-treeview` to avoid conflicts
* Fix: Send a 1x1 transparent thumbnail if thumbnail not available
* Filepages and File Categories now appear in Navigation Menus page -- You can add these to your navigation menu to easily link to a file details page. You can also link to file category listing the file pages in that category
* Fixed remote redirect
* Fixed remote file name detection
* Fixed file hit counter
* New template variable ``%is_mobile%`
* Fixed file list sorting bug
* Fixed file browser showing up unexpectedly
* Fixed permission issue in backend file browser

= 3.3.3 =
* DataTables update to 1.10.10
* Fixed backslashes in file data when adding
* Fixed `Could not store rsync meta`
* Template var `%file_small_icon%` added to dropdown menu
* Cloud Sync fixes

= 3.1.05 =
* Fixed AJAX calls
* Thumbnails not served through direct plugins script


= 3.1.04 =
* FileBrowser: new option `Inline Add` to toggle the display of Add File/Category links
* Async Uploader: Added error message on invalid server response after upload
* Prevent direct script access for Editor Plugin, Post Browser and AJAX
* PHP 7 compatibility: `mysql_close` only called if exists

= 3.1.03 =
* Added Extension Update API caching
* Load getid3_lib if necessary
* Fixed XXS URL redirection vulnerability found by [Cybersecurity Works](http://www.cybersecurityworks.com)

= 3.1.02 =
* PHP 7 constructor compatibility (and WP 4.3.0)
* Updated getId3
* Fixed sideload issue
* Made treeview drag&drop IE compatible
* Added delete buttons to backend file browser 
* Show icons in file/category selector tree
* Better sync progress reporting
* Improved sync performance, reduced server load during sync
* Removed FLV player, replaced with HTML5 video player
* Added compatibility for latest CF7
* Fix: More robust file name handling with special characters
* Fixed individual file force download option
* File browser: only show add category if user has permission
* Run a File Sync to fix category file counter bug (categories no opening in file browser)
* Inherit category upload permissions
* Deleting a category removes the folder
* New list template header/footer var: `%search_term%`
* Rescan looks for thumbnails with same basename if `Auto-detect thumbnails` is enabled
* Fixed fatal error in editor plugin with conflicting plugins
* Updated french translation by Marco Siviero
* Changed textdomain from 'wpfb' to 'wp-filebase' for language pack compatibility

= 3.1.01 =
* Added support for remote urls for local files with `file://` scheme
* New template variabla `%file_user_can_edit%`
* Updated DataTables to 1.10.4
* Updated DataTables column filter to 1.5.6
* Back-end filebrowser: hide edit button if not permitted
* Disable expiration time of thumbnail browser caching
* Fixed category file counter bug when adding new files causing categories not to expand in file browser
* Fixed pagination in back-end category list
* Fixed mysql table structure update causing `Unknown column` errors
* Fixed broken thumbnails when chaning category of a remote file
* Fixed pagination for lists
* Fixed MP3 cover image extraction

= 3.1.00 =
* New Feature: Treeview: Drag & Drop Files, Move Categories and Files by dragging
* New Feature: Bulk actions
* Added permissions check when creating categories
* Improved security (thanks to Venkateswara Reddy)
* Back-end filebrowser
* Made some user options global on Site Networks
* New Option: Disable WP-Filebse Stylesheet (wpfilebase.css)
* Batch Uploader fixes
* New template variable `%cat_edit_url%`
* New feature: Sort files by multiple fields
* Fixed conflcit with WP SEO where jQuery was not loading
* `%'` chars are escaped in download urls (see Github issue)
* Fixed performance issue when changing a file's category
* Fixed `preg_replace(): The /e modifier is deprecated`
* Fixed file list pagination links
* FB: add cat/add file
* Inline Add Cat
* Using `plugin_dir_url` function for better compatibility (from GitHub)
* Fixed search form (remove post type)
* Fixed context menu appearence
* Improved mobile responsive appearence on front and back-end
* Fixed Drag&Drop uploader issue when uploading file updates
* Fixed broken thumbnails of cloud files after changing the category

= 0.3.0.06 =
* New Feature: File URL: Prepend asterisk (*) to linktext to open in new tab
* Chinese translation by [Darlexlin](http://darlexlin.cn/)
* Added Google Universal Analytics compatibility
* New batch uploader field: File Display Name
* New File Browser code
* Fix: Suppressing deprecation errors on AJAX requests
* Fixed output suppression during Ajax requests
* Fixed: keep thumbnail during file update
* Fixed permission control for roles with names shorter than 4 (or mysql ft_min_word_len)

= 0.3.0.05 =
* Reverted 'direct access block' since this is not working for multi-site in some cases

= 0.3.0.04 =
* Fixed a conflict with NexGEN Gallery Plugin by disabling its resource manager output buffer
* Improved security: direct access of plugin files is blocked if plugin is disabled

= 0.3.0.03 =
* Enhanced Search Functions: added dash (-) operator to exclude words, added wildcard (*)
* Shortcode file links can be opened in new tab
* New File Template variable `%cat_id%`
* Updated getID3 to 1.10
* Fixed: Added img alt attributes
* Added function to reset hits (see Settings / Downloads)
* Added column filter dataTables plugin
* Fixed usage of `wp_check_filetype`
* GUI adjustments to fit latest WordPress version
* Fixed Security Issue in `GetFileHash` (thanks to [Samir Megueddem](http://www.synacktiv.com))
* Fixed image urls in custom CSS stylesheet
* Improved Sideload
* Fixed typos and update language files
* Fixed some permission issue for edit permissions and editor plugin
* Fixed file download permission issue
* Fixed general permission bug, where user roles were not loaded (added get_role_caps())


= 0.3.0.02 =
* Fixed batch uploader
* Fixed fake MD5 issue when downloading
* Fixed file browser icon

= 0.3.0.01 =
* New File List Table in Dashboard
* Custom Folder Icons for File Browser
* Improved CSS & thumbnail loading time
* Editor Plugin remembers extendend/simple form
* Added Sync Option `Fake MD5` to improve performance if MD5 not required
* Added iframe preview for templates
* Missing categories auto remove during sync
* Disabled Visual Editor for File Description in Editor Plugin
* Changed Category List Widget: If Root Category is Empty, all childs are displayed
* Updated jQuery treeview plugin. There were some CSS changes, please check your File Browser!
* Added mime type `application/x-windows-gadget`
* Fixed blank Editor Plugin screen occuring with some 3-rd party plugins
* Fixed HTML escaping for some file template vars
* Fixed feedback page when creating a category with the widget
* Fixed ID3 tag detection for files with large meta data
* Fixed mysql_close during download
* Fixed some strict standard warnings
* Fixed sync error handling
* No auto version on files like `01.01.01-01.02.03.mp3`
* Removed WP-Filebase test coockie

= 0.2.9.37 =
* Fixed Batch Uploader
* Further memory optimizations
* Updated DataTables to 1.9.4
* Fixed monthly/daily traffic limit
* Fixed download range header handling (thanks to mrogaski)
* Minified DataTables init JS to prevent auto p-tags
* Added `wpfilebase_file_downloaded` hook for download logging
* Fixed HTML escaping for some file template vars


= 0.2.9.36 =
* New Feature: Drag&Drop Batch Uploader with Upload Presets
* New fresh looking default File & Category templates. [HTML/CSS for upgrading](https://wpfilebase.com/how-tos/file-category-template-v2/)
* Added MP4 mime type
* Small Icon Size can be set to 0 to display full size icons
* Sync: missing thumbnails are removed from database
* Sync recognizes moved files so meta data is retained and only the path will be updated
* Updated SK translation by Peter Šuranský
* Memory optimizations
* Resetting settings to default will not reset the default templates anymore
* Resetting templates to default will also reset default templates
* New category template variable `%cat_has_icon%`
* Fixed auto p tags in JS
* Removed line breaks from search form HTML to prevent auto-<br>-tags
* Fixed HTML comments in templates
* Fixed file size bug for big files
* Fixed URL issues when using HTTPS
* Bulk Actions NOT included yet, planned for next update. Sorry for the delay!

= 0.2.9.35 =
* Increased stability of sync
* Backend: Fixed not all files beeing visible for Admins
* Fixed Editor Plugin flash uploader
* Fixed minor bugs
* Upload permissions are inherited
* New Option 'Use fpassthru' to avoid invalid download data on some servers
* New GUI tab for File Page Templates
* Removed Option `Destroy session when downloading`, this will now work in a different way
* Fixed flash uploader behavior when uploading file updates
* Fixed file renaming on upload
* Fixed quote escaping in template IF expressions


= 0.2.9.34 =
* Custom language files dir can be set with PHP constant WPFB_LANG_DIR in wp-config.php
* Fixed quote escaping in template IF expressions
* Fixed flash uploader behavior when uploading file updates

= 0.2.9.33 =
* New Option: Search Result Template
* Added complete un-install (Button located at WP-Filebase dashboard bottom)
* Fixed download URLs for file names containing `'`
* Files added with multi uploader are added directly after upload finished
* File Form: Licenses are hidden if none specified in Settings

= 0.2.9.31 =
* Fixed fatal error occuring in PHP Versions before 5.3 (`func_get_args() can't be used as a function parameter`)
* Fixed CSS loading issue

= 0.2.9.29 =
* Added pagenav checkbox to editor plugin
* Added Visual Editor for File Description
* New Option: Default File Direct Linking
* DataTables are now sorted according to the Shortcode argument `sort`
* Fixed minor bugs
* Fixed context menu on DataTables
* Added ID display to back-end Category list
* Shortcodes are parsed in template preview
* Removed deprecated file list widget control
* Decreased time for cache revalidation when downloading a File
* Fixed Extended/Simple Form toggle
* Admins can download offline files

= 0.2.9.28 =
* Made code adjustments for WordPress 3.5 compatibility
* New Option `Small Icon Size` in File Browser settings to adjust the size of icons and thumbnails
* Improved compatibility with custom Role Plugins
* Some GUI changes
* Fixed 'Cheating uh?' bug when using the category seaech form after editing (thanks to David Bell)
* Fixed secondary category query causing files to appear in root folder
* Removed call wp_create_thumbnail which is deprecated since WP 3.5
* Widget File Search Form now looks like the default search form
* Added length limit for template variables: `%file_display_name:20%` limits the name to 20 characters
* Fixed pagenav shortcode parameter, thanks to yuanl
* Fixed file size limit in Drag&Drop uploader causing trouble
* Fixed CSS Editor Bug
* Fixed bug in list sorting

= 0.2.9.27 =
* Fixed AJAX tree not showing

= 0.2.9.26 =
* Fixed flash uploader
* Fixed admin bar context menu
* Re-organized some settings tabs
* Missing files will automatically set offline during sync
* Updated Brazillian Portuguese translation by Felipe Cavalcanti
* Fixed Item::GetParents() stuck in endless loop

= 0.2.9.25 =
* [WP-Filebase Documentation](https://wpfilebase.com/documentation/) and [WP-Filebase Pro](https://wpfilebase.com/) released
* Added Category Owners
* Raised limits of file name length: file name 300, category folder name: 300, total path length: 2000
* Fixed invalid AJAX reponses caused by interfering plugins
* Fixed security issues in category management

= 0.2.9.24 =
* Added field to rename files in file upload form
* Configuration of old File Widget will be retained on update. Please change to the new multi-instance widget after updating!
* New Option `Inaccessible category message`
* Improved access permission handling for AJAX tree
* Fixed OpenOffice download link
* New template variable `%cat_user_can_access%` and `%file_user_can_access%`
* Files are only re-scanned if changed
* Fixed external MD5 hashing on Windows
* MySQL connection are closed during download
* New Template varialbe `%file_cat_folder%`
* Added sync debug info when query variable `debug` is set to 1 (add &debug=1 to the sync page URL and see the HTML source for backtrace) 
* Inaccessible categories are displayed in lists, but their content cannot be viewed
* Fixed resources URL when using SSL
* Removed HTML align property for category icons according to HTML5 standard 

= 0.2.9.23 =
* Configuration of old File Widget will be retained on update. Please change to the new multi-instance widget after updating!

= 0.2.9.22 =
* Multi instance File List Widget (old one is deprecated!)
* DataTable List template is automatically added
* New Template `Download-button`
* Fixed missing argument warning for `TitleFilter`
* Fixed Post Browser permission (now usable by Authors & Contributors)

= 0.2.9.21 =
* Improved template engine performance
* New option `Destroy session when downloading`
* jQuery [DataTables](http://datatables.net/) included. See the default data table template (you have to reset to default templates)
* New default template for DataTables
* Extended upload form in Editor Plugin
* Fixed widget upload permissions
* Fixed using file extensions as thumbnail extensions (petebocken)
* Fixed category sorting in multi categories lists
* Fixed display of attachments in post lists  (njsitebuilder)
* Fixed filemtime error when adding URLs (altafali)
* Fixed default permissions settings
* Fixed AJAX response issue that broke the file browser on some servers
* Added mimetype `application/notebook`
* Changed wp-load in Editor Plugin (might fix blank screen) 

= 0.2.9.19 =
* Upload widget can be used by guests now!
* New Option: `Frontend upload` controls the upload widget access
* New Option File Browser option: `Files before Categories`
* New Option: `Default Category`
* Sync code re-written
* Improved sync performance and stability by using external md5 program if available
* Editor Plugin: Selected Text is used for file links
* Memory usage on activation is limited now
* Fixed file renaming when uploading an update with same name
* Fixed escaping of apostrophes in file names
* Fixed sync progress bar
* Fixed multi inclusion of BMP thumbnail class that could break syncing
* Fixed live preview of list templates
* Fixed French translation by Marco Siviero
* Made some string localizable
* Removed deprecated category widget

= 0.2.9.18 =
* Added Category Sorting for file lists
* MD5 displayed in file form
* Safer file tree script loading

= 0.2.9.17 =
* Fixed category edit link
* New option `Late script loading` for file browser
* Updated getID3() to 1.9.3
* Removed context menu shadow

= 0.2.9.16 =
* Fixed download permalinks
* Fixed file list search

= 0.2.9.15 =
* Slovak translation by Peter Šuranský
* Fixed query strings in download URLs
* Fixed category icon caching
* Fixed search widget

= 0.2.9.14 =
* French translation by Yann Charlon
* Persion translation by Mahdi Maftouhi
* Lithuanian translation by [Vincent G](http://www.host1free.com/)
* Removed deprecated widget
* Fixed search widget
* Fixed caching bug `Wrong parameter count for trim()`
* Fixed missing reference to `GetRelPath`
* Fixed '#' in file names
* Fixed file date reset when syncing
* Fixed JS string escaping in editor plugin
* Fixed thumbnail generator logic
* Minor bug fixes

= 0.2.9.13 =
* Fixed file permissions bug

= 0.2.9.12 =
* New option `Attachments in post lists` to show attachments on index and search results
* Added search widget
* Custom category order
* Changed file edit permissions: only users that can `edit_others_posts` (usually editor role and above) can edit others files
* New category widget, old one deprecated!
* Fixed content filter behaviour: not appeding the file browser code to any content like widgets
* Fixed title encoding bug
* Better CSS caching

= 0.2.9.11 =
* Improved CSS loading time making pages faster when loaded for the first time
* New Drag and Drop Uploader
* Belarusian, [Alexander Ovsov](http://webhostinggeeks.com/science/)
* Fixed thumbnail behavior if image is smaller than thumbnail max size
* Cached thumbnails will reload on name changes 
* Fixed CSS on admin files page

= 0.2.9.10 =
* Removed incompatible flash upload for WP 3.3 and later
* Added file template variables %cat_small_icon%, %cat_icon_url%, %cat_url% (only defined for files that have a category)
* Fixed SQL escaping bug
* Fixed role string explode bug
* Fixed admin bar for WP 3.3

= 0.2.9.9 =
* New option for date format
* Added *How to get started with WP-Filebase?*
* Fixed upload issue `No file was uploaded`
* New Feature: File Tags (not fully implemented yet)
* Fixed Widget dashboard issue


= 0.2.9.8 =
* Fixed blank dashboard
* Fixed script enqueuing for file browser

= 0.2.9.7 =
* Flash upload with progress bar
* New Format for file link shortcodes (old ones still work of course)
* Improved permission handling
* Improved sideloading with progress bar
* Switchable remote file scanning
* Improved file upload form
* Better category icon detection (`folder.jpg`)
* New option *Disable Name Formatting*
* Fixed automatic MP3 cover art extraction
* Fixed dashboard file list not showing offline files
* Fixed upload path bug
* Minor bug fixes
* File uploader is now called "owner"

= 0.2.9.6 =
* Updated getID3() to 1.9.1
* Fixed class loading causing broken widget page

= 0.2.9.5 =
* Fixed HTTP caching issue
* Improved thumbnail caching
* Minor Bug Fixes

= 0.2.9.4 =
* Fixed MySQL Syntax Bug causing empty file lists in dashboard and front-end
* Added option `Private Files`
* File list fixes
* Added Form Default options for Permission and Author
* Template field `%file_added_by%` is now replaced by User Name
* GUI improvements
* Form security improvements
* Fixed download denied bug for custom user roles
* Users are redirected if `Inaccessible file message` is a URL
* Download limit fix

= 0.2.9.3 =
* Search integration: File Browser post is listed in search results whith matching files
* File permissions are inherited from categories when added or moved
* New Option `Thumbnail Path`
* New Option `Use path instead of ID in Shortcode`
* Fixed search issues
* Fixed custom tags bug
* Fixed change category bug
* Fixed ID3 Keyword encoding bug
* Fixed file list search form
* Fixed file list with multiple categories

= 0.2.9.2 =
* New feature: custom order for attached files
* Removed HTML escaping in file description and custom fields
* Fixed custom fields search and sorting

= 0.2.9.1 =
* New widgets: upload files and create categories from the front-end
* *Custom Fields* add even more file properties, searchable and sortable
* Multiple roles file permissions (select one or more user roles for limiting file access) 
* Italian translation
* Turkish translation (thanks to Mahir B. Aşut)
* New option `Attachment Position`
* New option: Search Integration now switchable
* Fixed password protected post attachments
* Fixed admin file search
* Fixed file downloads with special characters in URL
* Fixed SQL exploit, thanks to [Miroslav Stampar](http://unconciousmind.blogspot.com/)
* Minor bug fixes
* For developers: new action `wpfilebase_sync` to sync WP-Filebase from your plugin

= 0.2.9 =
* New Feature: Files are scanned for ID3 tags and other data that can be displayed in templates
* Automatic thumbnails out of MP3 cover arts
* New default templates for MP3 files
* New default template for FLV videos (including player)
* Added option for disabling HTTP Caching
* Re-structured setting tabs
* Thumbnail fix

= 0.2.8.5 =
* Added support for multiple embedded AJAX file tree views (file browsers)
* Edit links for changed files when syncing
* Added file extension blacklist
* Fixed an issue with CKEditor
* Better syncing of file dates
* Disabled cron sync by default

= 0.2.8.4 =
* Fixed JavaScript enqueue issues conflicting other plugins

= 0.2.8.3 =
* Fixed context menu for file links pointing to the associated page/post
* Images are considered as thumbnails for files with the same name when syncing
* Attachment listing now works outside the loop on a single post/page

= 0.2.8.2 =
* New file browser sort options
* Files can be sorted by ID
* Search integration fixed
* Fixed issues for multi site support
* Fixed template management tabs

= 0.2.8.1 =
* Fixed File Browser sorting
* Improved filename version detection

= 0.2.8 =
* New sort option `file_category_name` to sort files by the name of their category
* Editor Plugin: Added option to include all categories in a file list
* Post selector lists private posts now
* Added warning in content if list template does not exists (only visible for editors)
* New option `Send HTTP-Range header`
* New option `Redirect to referring page after login`
* Fixed search SQL: search was listing drafts with attached files

= 0.2.7 =
* Fixed sideload bug and improved support for large files
* Fixed file form presets bug
* Shortcode without quotes to increase compatibility with custom visual editors

= 0.2.6 =
* Made Editor Plugin compatible with [CKEditor](http://wordpress.org/extend/plugins/ckeditor-for-wordpress/)
* Fixed AJAX response when using WP-Minify
* Fixed script enqueue bug in editor_plugin.php

= 0.2.5 =
* Fixed several bugs occurring with MySQL strict mode
* Fixed compatibility issue in the Editor Popup with other plugins

= 0.2.4 =
* Added search form for file list (use `%search_form%` in list template footer or header)
* New option `Automatic Sync` which enables hourly synchronizations
* Files are now hidden when offline if `Hide inaccessible files` is enabled
* Fixed file browser
* Fixed post browser

= 0.2.3 =
* File browser is sorted with default settings now
* Existing files can now be attached to a post in editor plugin
* New option `Protect upload path`
* Fixed issue with AJAX file tree in `wpfb-ajax.php`
* Fixed broken delete function in context menu
* Some JavaScript optimizations

= 0.2.2 =
* Scrollbars in post browser
* Fixes Title issues in post browser
* Fixed admin dashboard form
* Fixed download_denied bug

= 0.2.1 =
* Sortable, paginated file lists
* New tree view post browser
* WP Search integration (search includes post attachments)
* Categories can be exluded from file browser
* Improved Widgets (you can specify a category for the file list now)
* Swedish translation by Håkan
* Russian translation by [L4NiK](http://lavo4nik.ru/plagin-zagruzki-fajlov-dlya-wordpress-wp-filebase-na-russkom/)
* Added context menu to file links for direct editing and deletion
* New Admin Menu Bar integration
* Admin Dashboard Widget
* Improved editor plugin
* New tree view file browser
* New Option *Hide download links*
* Added support for remote files (sideload and redirect)
* Template preview
* Removed deprecated user level system, using Roles for file and category permissions now
* New TPL vars `%post_id%`, `%file_type%`, `%file_extension%`
* Output buffering fix to trim any interfering output when downloading
* File name version detection (i.e: `sample-v1.2.ext`)
* Small thumbnails in admin file list
* Added RAR file type
* Fixed 404 bug when home url differs from site url
* Added anchor to category links
* Fixes, fixes and new bugs ;)

= 0.1.3.4 =
* Fixed blank tools page caused by empty Wordpress upload path
* Added notice if WP-Filebase upload path is rooted

= 0.1.3.3 =
* Brazillian Portuguese translation by [Jan Seidl](http://www.heavyworks.net/)
* French translation by [pidou](http://www.portableapps-blog.fr/)

= 0.1.3.2 =
* Added daily user download limits
* JavaScript errors caused by jQuery tabs function are suppressed if not supported by the browser
* Added support for custom file type icons. Copy your icons to `wp-content/images/fileicons` (see FAQ).

= 0.1.3.0 =
* Added option *Parse template tags in RSS feeds*
* New Widget: Category list
* Settings are organized in tabs now
* Conditional loading of WP-Filebase's JS
* Automatic login redirect
* Validated template output (**Note**: line breaks are not converted to HTML anymore, so please add &lt;br /&gt;'s or reset your settings to load the default template)
* Added localization support
* German translation
* Editor Button code changes
* Changed default file permissions from 777 to 666
* Fixed file date bug causing a reset of the date

= 0.1.2.4 =
* New option *Category drop down list* for the file browser
* Fixed sync bug

= 0.1.2.3 =
* Added support for custom Category Icons
* Fixed `file_url` in the download JavaScript for proper tracking
* Fixed a thumbnail upload bug

= 0.1.2.2 =
* Files and categories in the file browser are sorted now
* Category directories are now renamed when the folder name is changed
* Fixed file browser query arg
* Fixed Permalink bug

= 0.1.2.1 =
* New feature: category template for category listing
* New feature: added file browser which lists categories and files
* Added option to disable download permalinks
* New option *Decimal file size prefixes*
* Fixed a problem with download permalinks
* Fixed an issue with auto attaching files
* Fixed a SQL table index issue causing trouble with syncing
* Fixed a sync bug causing categories to be moved into others

= 0.1.2.0 =
* Added multiple templates support (you can now create custom templates for file lists)
* Added option *Hide inaccessible files* and *Inaccessible file message*
* When resetting WP-Filebase settings the traffic stats are retained now
* Fixed *Manage Categories* button
* Enhanced content tag parser
* Added support for HTTP `ETag` header (for caching)
* Improved template generation

= 0.1.1.5 =
* Added CSS Editor
* Added max upload size display
* Fixed settings error `Missing argument 1 for WPFB_Item::WPFB_Item()`
* Fixed widget control
* Fixed an issue with browser caching and hotlink protection

= 0.1.1.4 =
* Download charset HTTP header fixed
* Editor file list fixed
* New file list option `Uncategorized Files`

= 0.1.1.3 =
* Added FTP upload support (use `Sync Filebase` to add uploaded files)
* Code optimizations for less server load
* File requirements can include URLs now
* Fixed options checkbox bug
* Fixed an issue with the editor button
* Fixed form URL query issue
* Some fixes for Windows platform support

= 0.1.1.2 =
* Fixes - for PHP 4 only

= 0.1.1.1 =
* Now fully PHP 4 compatible (it is strongly recommended to update to PHP 5)
* Fixed a HTTP header bug causing trouble with PDF files and Adobe Acrobat Reader
* New option *Always force download*: if enabled files that can be viewed in the browser (images, videos...) can only be downloaded (no streaming)
* Attachement lists are sorted now
* The MD5 hash line in the file template is now commented out by default
* Fixed `Fatal error: Cannot redeclare wpfilebase_inclib()`

= 0.1.1.0 =
* Added simple upload form with less options which is shown by default
* Fixed editor button
* Changed editor tag box
* Selection fields in the file upload form are removed if there are no entries
* You can now enter custom JavaScript Code which is executed when a download link is clicked (e.g. to track downloads with Google Analytics)
* If no display name is entered it will be generated from the filename
* Removed the keyword `private` in class property declarations to make the plugin compatible with PHP 4
* Serveral small bug fixes
* CSS fixes
* Optimized code to decrease memory usage

= 0.1.0.3 =
* Added file list sorting options
* Rearranged options
* Fixed `Direct linking` label of upload form
* Added HTML link titles of the default template (to enable this change you must reset your options to defaults)

= 0.1.0.2 =
* Fixed a HTTP cache header
* Added support for HTTP If-Modified-Since header (better caching, lower traffic)

= 0.1.0.1 =
* Added download permissions, each file can have a minimum user level
* New Editor Tag `[filebase:attachments]` which lists all files associated with the current article
* Fixed missing `file_requirements` template field. You should reset your WP-Filebase settings if you want to use this.

= 0.1.0.0 =
* First version

== Upgrade Notice ==

= 0.2.0 =
PHP 5 or later required! This is a big upgrade with lots of new features. You have to convert old content tags to new shortcodes. Go to WP-Filebase management page and you should see a yellow box with the converter notice (backup the Database before!). And sync the filebase after that!

== Documentation ==
[WP-Filebase Documentation](https://wpfilebase.com/documentation/)

== Translation ==
If you want to translate WP-Filebase in your language, open `wp-filebase/languages/template.po` with [Poedit](http://www.poedit.net/download.php) and save as `wpfb-xx_YY.po` (`xx` is your language code, `YY` your country). Poedit will create the file `wpfb-xx_YY.mo`. Put this file in `wp-filebase/languages` and share it if you like (attach it to an email or post it on my blog).

== Plugin Developers ==
WP-Filebase currently offers the action `wpfilebase_sync`. This will run a fast filebase sync that adds new files.

The hook `wpfilebase_file_downloaded` with file_id as parameter can be used for download logging.

[WP-Filebase on GitHub](https://github.com/f4bsch/WP-Filebase)


== WP-Filebase Pro ==
[WP-Filebase Pro](https://wpfilebase.com/) is the commercial version of WP-Filebase with an extended range of functions. It supports secondary categories, extended permissions, embedded upload forms. Furthermore it can generate PDF thumbnails, sync with Dropbox or FTP and includes an improved file sync algorithm.

== Traffic Limiter ==
If you only want to limit traffic or bandwidth of media files you should take a look at my [Traffic Limiter Plugin](http://wordpress.org/extend/plugins/traffic-limiter/ "Traffic Limiter").


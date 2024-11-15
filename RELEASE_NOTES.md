# Release notes

## Version 2024-12
*Release date: ...*

### Changes
- Added unique validation for invite translation - language id #59
- Added unique validation for faq questions - question #59

### Small changes
- Updated dependencies


## Version 2024-11
*Release date: ...*

### New features
- Added barchart for amount of courses per country to course statistics dashboard #105
- Added barchart for amount of courses per education type to course statistics dashboard #105
- Added possibility to view only the errors from the log #81

### Small changes
- Updated dependencies
- Updated Composer to 2.8.2
- Removed email addresses from api output #109
- Added logging for mailman sync #80

### Bug fix
- Fix: Course start dates are shown earlier in different timezone #113
- Fix: User account can be approved before email address is confirmed #108


## Version 2024-10
*Release date: 2024-11-06*

### Changes
-  Migrated to CakePHP 4.5.7 and PHP 8.3 #85

### Small changes
- Added ddev config
- Updated Composer to 2.8.1
- Updated dependencies
- Required PHP version 8.3 or later, since version 8.2 active support ends on 2024-12-31


## Version 2024-09/3
*Release date: 2024-10-30*

### Bug fix
- Fix: Required file missing - sitemap.xml #110


## Version 2024-09/2
*Release date: 2024-10-22*

### Bug fix
- Fix: Required file missing - search_list.json #110 


## Version 2024-09 
*Release date: 2024-10-05*

### Bug fix
- First set php path in includes.sh #110


## Version 2024-07
*Release date: 2024-07-31*

### Bug fix
- Fixed missing imprint text #107


## Version 2024-06
*Release date: 2024-07-28*

### Small changes
- Optimized startdate validation #96
- Zoom and center map on Europe #103
- Unblocked info page in robots.txt
- Updated Composer to 2.7.7
- Updated dependencies


## Version 2024-05
*Release date: 2024-05-30*

### New feature
- Added ability to manage countries in the login area #92

### Bug fix
- Added server-side validation of start-dates #96

### Small changes
- Updated Composer to 2.7.6
- Created initial migration of db scheme
- Added explanation about new API url #104
- Added url validation for course-info link


## Version 2024-02
*Release date: 2024-03-26*

### Bug fixes
-  Moderator does not receive notification of new course #97 

### Small changes
- Removed 5 cc_config tables (remainder of removed ops module) #none
- Removed empty and unused table emails #none
- Removed column cakeclient_prefix (remainder of ops module) from user_roles #none
- Removed 8 tables (remainder of course reminders) #100
- Updated dependencies


## Version 2023-12
*Release date: 2024-02-27*

### Bug fixes
- Restructured php paths, intl module and cron jobs #93
- Removed course alerts #100

### Changes
- Added CLARIN and DARIAH logo's on the main page #87
- Course Statistics: Added chart of new courses per month #89
- Include unsubscribe information in course reminders #95
- Removed API v1 #83
- Added review reminders
- Changed the colour of hyperlinks embedded in the text #94

### Small changes
- Updated dependencies
- Upgraded CakePHP to 4.5.2
- Upgraded Composer to 2.7.1
- Updated dependencies for API
- Updated README and removed old ops text #40
- Updated Node.js 16 actions #99


## Version 2023-11
*Release date: 2023-12-18*

### Small changes
- Clearify user roles at User, Edit #79
- Course Statistics: Added link on title #89
- Course Statistics: Show top 25 new courses instead of 15 courses #89


## Version 2023-10
*Release date: 2023-10-04*

### Bug fix
- Fix: cron jobs for search bar, sitemap and course reminders don't work #93


## Version 2023-09
*Release date: 2023-09-19*

### Changes
- Changed Mapbox API keys to developer independent keys #28
- Added (Mapbox) attribution to map on main page #78
- Migrated to Heroku 22 (Ubuntu 22.04 LTS)

### Small changes
- Upgraded course add-edit map (institution location view/selector) to Mapbox 2.9.1
- Reduced amount of text which is logged when generating search list (GenSearchListCommand)
- Upgraded CakePHP to 4.4.17
- Upgraded Composer to 2.6.3
- Composer update for both API's
- Centralized PHP path setting for cli scripts

### Bug fixes
- Fixed deprecation warning: passing null into ucfirst at users view
- Fixed: Emails from the application are rejected by Gmail #69 


## Version 2023-08/2
*Release date: 2023-08-02*

### Bug fix
- Added Sender and ReturnPath to course reminder mails #69
 

## Version 2023-08
*Release date: 2023-08-01*
 
### New feature
- Implemented sitemap for all public shown courses and static links #55

### Change
- Migrated application to PHP 8.2 #65

### Small changes
- Upgraded CakePHP to 4.4.15
- Upgraded PhpUnit to 9.6.9
- Upgraded Composer to 2.5.8
- Upgraded CakePHP CodeSniffer to 4.7.0
- Info page - Content restructuring #23
- Added focus to email field at login page


## Version 2023-05
*Release date: 2023-06-27*
 
### New feature
 -  Integrated contributor mailing list into application #51

### Small changes
 - Changed Google Recaptcha keys to organisation keys #29
 - Implemented new bug reporting process #41
 - Changed course description to required field #74
 - Changed from email address to helpdesk instead of no-reply #69
 - Automatically remove leading and trailing spaces from course name #73
 - Increased size of checkboxes #37


## Version 2023-04
*Release date: 2023-06-27*
 
### New feature
- Searchbar: Find a course directly by name or institution #62

### Change
- Implemented requirement that combination of course name, institution and education type has to be unique #59

### Small changes
- Removed redundant social media links and usage of external file resource content.js #68
- Introduced generic email address notation and avoid direct mailto links #67
- Replaced sitemap wording by logo in menu's
- Upgraded jQuery to 3.6.4
- Upgraded CakePHP to 4.4.13
- Changed date to ISO format in course detail page


## Version 2023-03
*Release date: 2023-04-25*  

### Changes
- Introduced new DHCR Helpdesk email address in UI and application logic
    - Updated explanation at contact us and national moderators to point to the new helpdesk email address #42
    - Changed "from" and "cc" email addresses to new stucture #42
    - Introduced "reply to" email address and "from" as no-reply@ #42

### Small changes
- Added explanation at add/edit course for the situation an institution is not in the list #58
- Upgraded dependencies to recent versions
- Upgraded composer to 2.5.5

### Bug fixes
- Fix: User edit page does not save data when changing user role #60
- Fix: Confusing UI response on instant new user account approval #64
- Fix: Don't show alternative field in new user account approval mail, when user selected an institution
- Fix: Make institution name available in new user account approval mail
- Fix: Number of courses: API vs. DHCR Statistics #63 


## Version 2023-02
*Release date: 2023-04-05*

### Changes
- Implemented notification mail for moderators when a new course is waiting for approval #54 
- Migrated API v1
    - to the latest version of the framework (4.4)
    - to PHP 8.1
- Migrated API v2
    - to the latest version of the framework (4.4)
    - to PHP 8.1
- Migrated the main application [dhcr-main]
    - to the latest version of the framework (4.4)
    - to PHP 8.1

### Small changes
- Changed date format in user approval mail to ISO format
- Added App Info section (shows version numbers of dependencies) to statistics dashboard
- Added CC (legacy logging) for user approval mail
- Enabled debug mode for dev instance
- Changed column name "active" to "published" in all the course lists in the admin area
- Optimized sorting in Pending Invitations
- Added release dates for all previous versions to release notes


## Version 2022-12
*Release date: 2022-12-29*
 
### Small changes
- Updated date to ISO format in views
- Show newest log entry on top
- Added statistics for amount of outdated courses per country to course statistics
- Added traffic light for moderator participation to users statistics

### Bug fixes
- Fixed subscribing for course alerts and editing preferences
- Added unique for institution name; allow only one institution with the same name


## Version 2022-11/3
*Release date: 2022-12-28*

### Bug fixes
- Fix: Adding moderator to list, without photo upload
- Fix: Move moderator list to separate page, to avoid acordeon problem with a huge amount of content


## Version 2022-11/2
*Release date: 2022-12-06*

### New features
- National Moderators List
  - New public section, at the Info page, which shows information and photo of the moderators
  - New options in the backend (at user view/edit) where admins can upload a photo and enable public display

### Bug fixes / small changes
- Added graph to show how many courses will be archived soon
- Added list of 15 latest added courses to course statistics page
- Hidden filter buttons for Objects and Techniques
- Fix: Show all pending invitations when admin is also mod


## Version 2022-11/1
*Release date: 2022-11-10*

### New features
- FAQ questions and answers are added and shown for public, contributors and moderators

### Bug fixes / small changes
- Updated path for course reminders command to match new environment


## Version 2022-10
*Release date: 2022-10-31*

### New features
- Added list of moderators to the Contributor Network Dashboard (for admins only).

### Bug fixes / small changes
- Fix: Course duration units handling for add course
- Fix: Not approved courses are public visible #21
- Fix: Need to clear the contact form after succesful a sending message


## Version 2022-09
*Release date: 2022-10-27*

### New features
- FAQ Questions (the lists with questions are currently hidden, until content is added)
    - Displays a list of questions & answers pairs, based on login type, for:
      - Public (no login)
      - Contributor
      - Moderator
    - Features in the backend for admins: 
      - Add/edit
      - If necessary, add link title and url
      - Publish/Unpublish
      - Change order in which the items are shown
      - Move items between the lists for different login types
    - Everybody can read the public FAQ, without login
    - Contributors and Moderators can find their FAQs in the Help Dashboard, based on their user role.
      - Moderators can also access the Contributors FAQ
      - Administrators can access all FAQs
- Statistics
  - Summary Statistics, with numbers about:
    - Courses
    - Users
    - Institutions
    - Countries
    - Cities
    - FAQ Questions
    - Translations for the invite mail
  - Statistics about courses
  - Statistics about the number of users, moderators and admins

### Bug fixes / small changes
- Added social media section to menu
- Added link to release notes in UI
- Updated icons and button texts to be consistent


## Version 2022-08
*Release date: 2022-08-22*

### New features
- Transfer Course
    - Transfer a course to another user, in case somebody else will be teaching the course in the future
- Pending Invitations
    - Overview of invitations which has been sent to new users, including expiry time
    - Reinvite: possibility to send the invitation again, f.e. when the link has been expired
- Explanation for Moderators and Administrators
    - Topics:
      - Access and User Roles
      - How to Create an Account
      - Course expiry process
- Course expiry reminders
    - Reimplemented sending reminder mails for expired courses, incl. logging
- Added new user interface item to view log
- Introduced release notes
- Added date picker / input validation for start dates (at Add Course)

### Bug fixes / small changes
- Various text corrections / updates
- Removed duplicated social media links at info#publications
- Added timezone to password reset mail, to avoid confusion about expiry time
- Converted course expiry periods to months, to have the same unit everywhere in the application
- Added default email subject prefix to invite and reinvite emails


## Version 2022-05
*Release date: 2022-05-27*

This is a major release which contains a lot of big changes. The complete login area is new implemented as well as several backend processes. Also new features were added.

### Key points
- Clear user interface
- Self explaining, no manual or video tutorials needed
- New created menu structure
- Three ways of navigating: 
    1. Dashboards / tiles
    2. Menu
    3. Breadcrumbs
- Depending on the user role, additional options are available in the user interface (contributor -> moderator -> administrator)
- Revised texts and instructions for users

### New features
- A user can be administrator ***and*** moderator with the same account
- Notification area
    - Directly shows the number of items which needs attention
    - Reminder to subscribe mailing list
- Needs attention
    - Expired courses
    - Course approval
    - Account approval
- Administrate courses
    - Moderated courses
        - Overview of courses in the moderated country
    - All courses
- Contributor network
    - Invite user
        - Invitation mail can be sent in local languages
        - Page restructured for easier use
    - Moderated users
        - Overview of users in the moderated country
        - View user
            - Easy account troubleshooting
        - Edit user
            - Assign user to other institution
    - All users
- Category lists
    - Easy add or change related data
        - Cities, Institutions, Languages, Translations

### Reimplemented features from old version
- Administrate courses
    - Add course
    - My courses
- Profile settings
    - Change email
    - Change password
    - Subscribe to mailing list
    - Edit profile
About the Aeolus framework
==========================
The Aeolus Database Query system is a framework of PHP code and PostgreSQL tables 
and database functions, originally developed for internal use at the Bodleian Library, 
Oxford, UK, by Sushila Burgess. The acronym 'AEOLUS' originally stood for 'Answers 
to Enquiries from Oxford Library Users and Staff'. 

It is now available as open source software on https://github.com/BDLSS/Aeolus

The framework provides: 
 * a login page; 
 * pages to allow a supervisor to add and edit new users; 
 * a menu system; 
 * functionality to allow any database table or view to be queried, with paginated 
   results, and with the ability to email query results to oneself as a CSV attachment.

You can build on this framework in different ways: 

 * You can create your own SQL tables and then develop your own data entry forms and 
   add these to the framework to allow editing of your database.

 * Alternatively, you can make use of two existing open-source repositories which 
   build on the Aeolus framework: EMLO-edit and IMPAcT:

    * https://github.com/BDLSS/EMLO-edit

    * https://github.com/BDLSS/IMPAcT-edit 

   The database creation script provided in these two repositories sets
   up an entire suite of tables and database functions which can be used to handle 
   literary catalogues.  See the documentation in the EMLO-edit and IMPAcT 
   repositories for further details.

Installing the Aeolus framework
===============================
You need to have PostgreSQL 9 and PHP5 installed. Unfortunately we are still relying
on some deprecated settings. In the case of PostgreSQL, you need to have 
'standard_conforming_strings' set to 'off'. In the case of PHP, we still use Pear::DB 
which is no longer bundled with PHP, so you may need to install it. Then proceed as 
follows:

1. Extract everything from https://github.com/BDLSS/Aeolus

2. Edit private_defines.php

  * Change the settings of CONSTANT_LIVE_DATABASE_NAME, CONSTANT_TEST_DATABASE_NAME 
    and CONSTANT_DEV_DATABASE_NAME to the database names that you want to use.

  * Change the setting of DB_LOGFILE_DIR to a directory which can be written to by 
    your webserver daemon. Remember to make the path end in "/".

3. Edit aeolus.php (and optionally dev_aeolus.php) and set CONSTANT_SOURCEDIR to the 
   directory where you have installed the source code. Remember to make the path end 
   in "/".

4. Optionally, if you do not wish all your tables and usernames to begin with the 
   prefix 'aeolus', choose a different prefix and do a global search and replace in:

   * aeolus.php (change the setting of CFG_PREFIX)

   * create_aeolus_database_framework.sql (change the string 'aeolus' throughout)

   Similarly, if you do not wish the username of your main database administrator to 
   end in 'a1supr', replace the string 'a1supr' in:

   * private_defines.php (change the setting of SUPERVISOR_ROLE_CODE)

   * create_aeolus_database_framework.sql (change the string 'a1supr' throughout)

5. Move aeolus.php (and optionally dev_aeolus.php) into your Apache document root or 
   other directory where Apache can serve it up.

6. Move all Javascript files (extension .js) and image files (extensions .png, .gif, 
   .jpg) into the same directory where you put aeolus.php, i.e. into the Apache 
   document root or similar.

7. If the database name that you defined in private_defines.php does not yet exist, 
   create an empty database with that name now, as postgres, e.g. 

   createdb -U postgres nameoflivedb

8. Run the script create_aeolus_database_framework.sql as postgres against the 
   database that you want to use:

   psql nameoflivedb -U postgres < create_aeolus_database_framework.sql

   (Note: if you want to set up a copy of the EMLO-edit or IMPAcT databases, 
   run the database creation script from those repositories INSTEAD OF 
   create_aeolus_database_framework.sql)

9. Visit the URL corresponding to the directory where you put aeolus.php, e.g. 
   https://example.com/aeolus.php and log in with the following details:

   * Username: aeolusa1supr
   * Password: ChangeMeNowPlease!

   (Of course, if you have changed the system prefix from 'aeolus' and the supervisor 
   role code from 'a1supr', then the username will be formed from your new settings.)

10. Change the password for the above user via the 'Edit your own details' option of 
    the main menu.

11. Create further users as desired via 'Supervisor-only options' on the main menu.

12. Edit infrastructure.php and add your own logic for detecting cross-site scripting 
    and for deciding if a method is 'safe', i.e. read-only and unable to change the 
    database. (This step can be postponed, but MUST NOT be omitted, particularly when 
    your new Aeolus system goes into production.)

You can now proceed with your own development as described in the documentation that 
will be made available on: https://damssupport.bodleian.ox.ac.uk/trac/aeolus

(Sushila Burgess, 7th June 2013)

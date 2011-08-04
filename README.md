OFAPI
-----
OpenForum API for phpBB forum engine
Version 1.1 (2008/08/18)
Version 1.2 (2011/08/03) (with JSON-RPC support)

Requirements:
-------------
Version 3.0 or later of phpBB


What's OFAPI?
-------------
OFAPI mean OpenForum API and it's a set of functions (Application Programming Interface) made to allows external applications 
to comunicate with phpBB forums (since version 3.0) using standard XML-RPC (XML Remote Procedure Calls) protocol.
Using OFAPI you can create custom programs to browse your favourite forums (ie. a specific application for phones or other 
mobile devices) easily. OFAPI contains lots of functions well documented and tested (okay we still in beta stage) and it's 
open source.


How to install OFAPI?
---------------------
OFAPI is pretty simple to install: just execute `git clone git://github.com/wankdanker/ofapi.git` in your phpBB root.
Next... no wait there is not any other step. You are done!


How to use OFAPI to develop something?
---------------------------------------
You can comunicate with OFAPI using your favourite language (that supports XML-RPC comunication). We are developing 
frameworks and libraries for the most common environment such like Cocoa (ObjC on Mac) or Java (take a look to the site to 
know more).

To call a function you need to point your XML-RPC client to ofapi.php file.
You need to remember an important thing about OFAPI. Due to some implementations in phpBB in order to mantain your login 
each called function must be sent with both functions parameters and login data.

Specifically you need to send a dictionary with two elements: keyed obj 'auth' (an array with two elements, username and 
password, ordered) and keyed object 'data' that contains an array with functions params (in order).

Documentation
-------------
You can find docs about methods available in OFAPI browsing the doc folder.
Remember: when called function parameters are lower than minimal requested number GENERAL_WRONG_PARAMETERS will return as 
result of call.

Copyright
---------
OFAPI was created by Daniele Margutti and Roberto Beretta and the source code is based upon the original phpBB 3 code. 
The project is distribuited under GPL (General Public License). If you use OFAPI in your forum fell free to write some 
lines about the authors.

Origin
------
I found this code at http://code.google.com/p/ofapi/. It had not been touched since September, 2008. I figured it could
use a little JSON and github love.
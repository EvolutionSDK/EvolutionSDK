Security Bundle
===============
Handles security of the manage center, and adds the ability to use developer authentication to normal pages.

Using E3 Security
=================
You can activate security two ways. One you can either run the event `e::$events->framework_security();` or manually call `e::$security->developerAccess();` thats all there is to it.

Adding Users
============
Users are stored in `developers.yaml` inside your applications `./configure` folder. Here is an example setup. Users are stored in the format of username underscore md5-salted password.

	---
		user1: user1_2b8d489834fd5b32b15cf25bcfbde7ba
		user2: user2_38dd901dbaad1ebee6b722977f641a5a

To generate the user hases go to `<yourapp_url>/@security` and enter a user in the format of `<user>.<password>` and hit enter it will return a hash for you to add to `developers.yaml`.

Authentication
==============
When a authenticate request comes up you will login in the format of `<user>.<password>`
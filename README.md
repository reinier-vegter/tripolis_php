# tripolis_php
Tripolis (http://www.tripolis.com/) php class library

This bundle can be used as a php library to interact with Tripolis.
Since Tripolis has an enormous amount of WSDL's, this library might be a bit more pragmatic to use, instead of generating all classes with wsdl2php.

Since I only developed what I needed, there is a lot functionality that hasn't been covered.
Feel free to fork this project and propose pull-requests, so we can extend it.

Current functionality:
 - add contacts to Tripolis
 - fetch a contact
 - fetch contact-groups
 - add contacts to contact-groups
 - add articles + images
 - .....
 
 Read the classes to be sure what's in there.
 
 Have phun.
 
# Examples:

      $tripolis = new TripolisContactService($auth['client'], $auth['username'], $auth['password']);
      $tripolis->setDbId($auth['db']);
      $contact = $tripolis->searchByDefaultContactField($email);
--
      $tripolis->addToContactGroup($contact_id, $contact_groups, $confirmed = TRUE);

--
      $tripolis = new TripolisContactGroupService($client = $auth['client'], $username = $auth['username'], $password = $auth['password']);
      $tripolis->setDbId($auth['db']);
      $groups = $tripolis->getByContactDatabaseId();

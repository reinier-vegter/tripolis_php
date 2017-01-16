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
 - retrieve list of workspaces
 - retrieve list of article types (currently only based on workspace id)
 - .....

 Read the classes to be sure what's in there.

 Have phun.

# Notes
 - Each ...Service object needs a client-name, username and password.
 - The setDbId() method requires a database ID. At the time of writing, these can be found to log into Tripolis,
   and use a browser-inspector on the database-select-list, to find the corresponding ID.

# Examples

      $tripolis = new TripolisContactService($auth['client'], $auth['username'], $auth['password']);
      $tripolis->setDbId($auth['db']);
      $contact = $tripolis->searchByDefaultContactField($email);
--
      $tripolis->addToContactGroup($contact_id, $contact_groups, $confirmed = TRUE);

--
      $tripolis = new TripolisContactGroupService($client = $auth['client'], $username = $auth['username'], $password = $auth['password']);
      $tripolis->setDbId($auth['db']);
      $groups = $tripolis->getByContactDatabaseId();


# Development

## Available API 2.0 calls for SoHo (regular) license:
 - ContactService.addBulkToContactGroup
 - ContactService.addToContactGroup
 - ContactService.create
 - ContactService.createBulk
 - ContactService.delete
 - ContactService.deleteBulk
 - ContactService.geyById
 - ContactService.removeFromContactGroup
 - ContactService.update
 - ContactService.updateBulk
 - DirectEmailService.getByDirectEmailTypeId
 - DirectEmailService.getById
 - DirectEmailTypeService.getByWorkspaceId
 - NewsletterService.getById
 - NewsletterService.getByNewsletterTypeId
 - NewsletterTypeService.getByWorkspaceId
 - PublishingService.publishTransactionalEmail
 - SubscriptionService.subscribeContact

Rest of all calls needs a dedicated API license.

## ToDo
 - Take out .module (Drupal) file.
   The classes are usable without Drupal, but the project should be converted more into a PSR-4 compatible composer
   package.
   
# Background on Tripolis
When working with the Tripolis API, it is recommended to gather some insight on the TripolisDialog environment.

## Adding articles
On first glance the content structure of Tripolis seems somewhat tricky. Some helpful tips to get you on the way are in the list below. This will help you to understand why certain classes exist in this library.

 - Contacts live in contact groups - this means that when adding a contact you should have an id of the contact group you want to add the contact to ();
 - Content (articles and images) live in workspaces - this means when adding an article you should have an id of the workspace you want to add the article to. The tricky part in this case is that you will first need the workspace id to gather article types. Then, with the wanted article type id, you can add the article to the workspace.

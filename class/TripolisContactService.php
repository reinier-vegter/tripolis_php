<?php
/**
 * @file
 * Class to deal with contacts.
 */

/**
 * Class TripolisContactService.
 */
class TripolisContactService extends TripolisAPISoap {
  /**
   * Constructor function.
   *
   * @param string $client
   *   Client name.
   * @param string $username
   *   Username to use for API.
   * @param string $password
   *   API password.
   */
  public function __construct($client, $username, $password) {
    parent::__construct($client, $username, $password);
    $this->setupSoap('ContactService');
  }

  /**
   * Retrieve all contact groups based on database ID.
   *
   * @param array $fields
   *   Contact fields, containing key 'id' or 'name',
   *   and 'value'.
   *
   * @return string|bool
   *   New user ID, or FALSE
   *   on failure.
   */
  public function create($fields = array()) {
    if (!empty($this->dbId)) {
      try {
        // Set Soap body params.
        $param = array(
          'createRequest' => array(
            'contactDatabaseId' => $this->dbId,
            'contactFields' => array(
              'contactField' => $fields,
            ),
          ),
        );
        // Call API.
        $result = $this->call('create', $param);
        if (is_array($result) && isset($result['id'])) {
          return $result['id'];
        }
      }
      catch (SoapFault $e) {
        $this->throwError($e);
      }
    }

    return FALSE;
  }

  /**
   * Delete contact based on ID.
   *
   * @param string $id
   *   Contact ID.
   *
   * @return string|bool
   *   New user ID, or FALSE
   *   on failure.
   */
  public function delete($id) {
    if (!empty($this->dbId)) {
      try {
        // Set Soap body params.
        $param = array(
          'deleteRequest' => array(
            'id' => $id,
          ),
        );
        // Call API.
        $result = $this->call('delete', $param);
        if (isset($result['id'])) {
          return $result['id'];
        }
      }
      catch (SoapFault $e) {
        $this->throwError($e);
      }
    }

    return FALSE;
  }

  /**
   * Retrieve all contact groups based on database ID.
   *
   * @param array $param
   *   Search fields / values.
   *
   * @return array|bool
   *   Associative array containing merge of all paged requests, or FALSE
   *   on failure.
   */
  public function search(array $param) {
    if (!empty($this->dbId)) {
      try {
        // Set Soap body params.
        $param = array(
          'searchRequest' => array_merge(array('contactDatabaseId' => $this->dbId), $param),
        );

        // Call API paged.
        $result = $this->pagedSoapCall('search', $param);
        return $result;
      }
      catch (SoapFault $e) {
        $this->throwError($e);
      }
    }

    return FALSE;
  }

  /**
   * Fetch contacts based on value of the default contact field.
   *
   * @param string $value
   *   For example a email address.
   * @param string $operator
   *   EQUALS
   *   STARTS_WITH
   *   ENDS_WITH
   *   CONTAINS
   *   LESS_THAN
   *   LESS_THAN_OR_EQUAL
   *   GREATER_THAN
   *   GREATER_THAN_OR_EQUAL
   *   NOT_EQUALS
   *   DOES_NOT_CONTAIN.
   *
   * @return array
   *   Array containing 1 contact, or multiple contacts,
   *   of FALSE on failure.
   */
  public function searchByDefaultContactField($value, $operator = 'EQUALS') {
    // First, fetch DB info from Tripolis, to get the
    // ID of the default field (usually email).
    $db_api = new TripolisContactDatabaseService($this->client, $this->username, $this->password);
    $db_api->setDbId($this->dbId);
    if ($db = $db_api->getById()) {
      $field_id = $db['defaultContactDatabaseField']['id'];

      // Fetch contact list.
      $result = $this->search(array(
        'contactFieldSearchParameters' => array(
          'contactFieldSearchParameter' => array(
            'contactDatabaseFieldId' => $field_id,
            'operator' => $operator,
            'value' => $value,
          ),
        ),
        'returnContactFields' => array(
          'returnAllContactFields' => 1,
        ),
      ));

      if (isset($result['contacts']['contact'])) {
        return $result['contacts']['contact'];
      }
    }

    return FALSE;
  }

  /**
   * Add user to contact group(s).
   *
   * @param string $user_id
   *   User ID.
   * @param array $groups
   *   Array with group ID's as values.
   * @param bool $confirmed
   *   TRUE: yes, FALSE: no.
   *
   * @return string|bool
   *   New user ID, or FALSE
   *   on failure.
   */
  public function addToContactGroup($user_id, array $groups, $confirmed = TRUE) {
    if (!empty($this->dbId)) {
      try {
        // Set Soap body params.
        $subscription_groups_param = array();
        foreach ($groups as $group_id) {
          $subscription_groups_param[] = array(
            'contactGroupId' => $group_id,
            'confirmed' => $confirmed,
          );
        }
        $param = array(
          'addToContactGroupRequest' => array(
            'contactId' => $user_id,
            'contactGroupSubscriptions' => array(
              'contactGroupSubscription' => $subscription_groups_param,
            ),
            'reference' => $GLOBALS['base_url'],
          ),
        );

        // Call API.
        $result = $this->call('addToContactGroup', $param);
        return $result;
      }
      catch (SoapFault $e) {
        $this->throwError($e);
      }
    }

    return FALSE;
  }

}

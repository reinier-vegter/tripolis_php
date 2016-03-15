<?php
/**
 * @file
 * Class to fetch contact groups.
 */

/**
 * Class TripolisContactGroupService.
 */
class TripolisContactGroupService extends TripolisAPISoap {
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
    $this->setupSoap('ContactGroupService');
  }

  /**
   * Retrieve all contact groups based on database ID.
   *
   * @param string $type
   *   Group type.
   *
   * @return array|bool
   *   Associative array containing merge of all paged requests, or FALSE
   *   on failure.
   */
  public function getByContactDatabaseId($type = '') {
    if (!empty($this->dbId)) {
      try {
        // Set Soap body params.
        $param = array(
          'getByContactDatabaseIdRequest' => array(
            'contactDatabaseId' => $this->dbId,
            'groupType' => $type,
          ),
        );
        // Call API paged.
        $result = $this->pagedSoapCall('getByContactDatabaseId', $param);

        if (isset($result['contactGroups']['contactGroup'])) {
          return $result['contactGroups']['contactGroup'];
        }
      }
      catch (SoapFault $e) {
        $this->throwError($e);
      }
    }

    return FALSE;
  }

}

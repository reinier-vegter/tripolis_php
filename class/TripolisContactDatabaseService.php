<?php
/**
 * @file
 * Class to fetch contact groups.
 */

/**
 * Class TripolisContactDatabaseService.
 */
class TripolisContactDatabaseService extends TripolisAPISoap {
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
    $this->setupSoap('ContactDatabaseService');
  }

  /**
   * Get a database based on its ID.
   *
   * @param string $id
   *   The database id from Tripolis.
   *
   * @return object|bool
   *   DB object, of FALSE on failure.
   */
  public function getById($id = '') {

    // Pick object dbId by default.
    if (empty($id) && isset($this->dbId) && !empty($this->dbId)) {
      $id = $this->dbId;
    }

    // Make API call.
    if (!empty($id)) {
      try {
        $param = array('getByIdRequest' => array('id' => $id));
        $result = $this->call('getById', $param);
        if (isset($result['contactDatabase'])) {
          return $result['contactDatabase'];
        }
      }
      catch (SoapFault $e) {
        $this->throwError($e);
      }
    }

    return FALSE;
  }

  /**
   * Retrieve all databases for client.
   *
   * @return array|bool
   *   Associative array containing merge of all paged requests, or FALSE
   *   on failure.
   */
  public function getAll() {
    try {
      // Set Soap body params.
      $param = array('getAllRequest' => array());
      // Call API paged.
      $result = $this->pagedSoapCall('getAll', $param);

      // If only one database is available, structure is different than
      // in case of multiple db's.
      if (isset($result['contactDatabases']['contactDatabase'][0])) {
        return $result['contactDatabases']['contactDatabase'];
      }
      elseif (isset($result['contactDatabases']['contactDatabase'])) {
        return array($result['contactDatabases']['contactDatabase']);
      }
    }
    catch (SoapFault $e) {
      $this->throwError($e);
    }

    return FALSE;
  }

}

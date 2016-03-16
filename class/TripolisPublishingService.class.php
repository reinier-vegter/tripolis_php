<?php
/**
 * @file
 * Class to interact with Publishing Service.
 */

/**
 * Class TripolisPublishingService.
 */
class TripolisPublishingService extends TripolisAPISoap {

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
    $this->setupSoap('PublishingService');
  }

  /**
   * Get fields based on article workspace ID.
   *
   * @param string $id
   *   The workspace id from Tripolis.
   *
   * @param string $id
   *   The Status of the jobs.
   *
   * @return mixed
   *   Jobs  array or FALSE on failure.
   */
  public function getByWorkspaceId($id, $status = 'ENDED') {
    if (!empty($id)) {
      try {
        $param = array('jobsByWorkspaceIdRequest' => array('workspaceId' => $id, 'status' => $status));
        $result = $this->pagedSoapCall('getByWorkspaceId', $param);

        if (isset($result['jobs']['job'])) {
          return $result['jobs']['job'];
        }
        return $result;
      }
      catch (SoapFault $e) {
        $this->throwError($e);
      }
    }
    return FALSE;
  }

}

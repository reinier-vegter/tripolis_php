<?php
/**
 * @file
 * Class to interact with article fields.
 */

/**
 * Class TripolisArticleFieldService.
 */
class TripolisArticleFieldService extends TripolisAPISoap {

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
    $this->setupSoap('ArticleFieldService');
  }

  /**
   * Get fields based on article type ID.
   *
   * @param string $id
   *   The article type id from Tripolis.
   *
   * @return mixed
   *   Article array or FALSE on failure.
   */
  public function getByArticleTypeId($id) {
    if (!empty($id)) {
      try {
        $param = array('getByArticleTypeIdRequest' => array('articleTypeId' => $id));
        $result = $this->pagedSoapCall('getByArticleTypeId', $param);

        if (isset($result['articleFields']['articleField'])) {
          return $result['articleFields']['articleField'];
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

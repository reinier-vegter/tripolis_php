<?php
/**
 * @file
 * Class to interact with article types.
 */

/**
 * Class TripolisArticleTypeService.
 */
class TripolisArticleTypeService extends TripolisAPISoap {

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
    $this->setupSoap('ArticleTypeService');
  }

  /**
   * Get article types based on workspace ID.
   *
   * @param string $id
   *   The workspace id from Tripolis.
   *
   * @return mixed
   *   Article type array or FALSE on failure.
   */
  public function getByWorkspaceId($id, $article_fields = FALSE) {
    if (!empty($id)) {
      try {
        $param = array('getByWorkspaceIdRequest' => array('workspaceId' => $id, 'returnArticleFields' => $article_fields));
        $result = $this->pagedSoapCall('getByWorkspaceId', $param);

        if (isset($result['articleTypes']['articleType'])) {
          return $result['articleTypes']['articleType'];
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

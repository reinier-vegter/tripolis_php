<?php
/**
 * @file
 * Class to fetch articles.
 */

/**
 * Class TripolisArticleService.
 *
 * Methods to create articles.
 */
class TripolisArticleService extends TripolisAPISoap {

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
    $this->setupSoap('ArticleService');
  }

  /**
   * Get an article based on its ID.
   *
   * @param string $id
   *   The article id from Tripolis.
   *
   * @return mixed
   *   Article array or FALSE on failure.
   */
  public function getById($id) {
    if (!empty($id)) {
      try {
        $param = array('getByIdRequest' => array('id' => $id));
        if ($result = $this->call('getById', $param)) {
          $fields = array();
          if (!is_array($result)) {
            $this->throwError($result);
          }
          else {
            $article = $result['article'];
            foreach ($result['article']['articleFields']['articleField'] as $field) {
              // Fetch image url.
              if ($field['name'] == 'afbeelding' && !empty($field['value'])) {
                $image_api = new TripolisImageService($this->client, $this->username, $this->password);
                $fields[$field['name']] = $image_api->getImageFromId($field['value']);
                continue;
              }

              // Set field value.
              $fields[$field['name']] = $field['value'];
            }

            // Combine fetched fields into article.
            $article['fields'] = $fields;
            return $article;
          }
        }
      }
      catch (SoapFault $e) {
        $this->throwError($e);
      }
    }
    return FALSE;
  }

  /**
   * Get articles meta-data based on workspace ID.
   *
   * To fetch whole article (all fields), do so by calling
   * getById().
   *
   * Be aware that the result set might contain any type
   * of article, so fields might differ between articles.
   *
   * Be aware that this might fetch A LOT.
   * First, a paginated list is fetched for the workspace,
   * containing meta-data for all articles.
   *
   * @param string $id
   *   The workspace id from Tripolis.
   *
   * @return mixed
   *   Article array or FALSE on failure.
   */
  public function getByWorkspaceId($id) {
    if (!empty($id)) {
      try {
        // Fetch list of articles (meta data).
        $param = array('getByWorkspaceIdRequest' => array('workspaceId' => $id));
        if ($result = $this->pagedSoapCall('getByWorkspaceId', $param, 1000)) {
          if (!is_array($result)) {
            $this->throwError($result);
          }
          else {
            return $result['articles']['article'];
          }
        }
      }
      catch (SoapFault $e) {
        $this->throwError($e);
      }
    }
    return FALSE;
  }

  /**
   * Get articles based on newsletter ID.
   *
   * Be aware that the result set might contain any type
   * of article, so fields might differ between articles.
   *
   * @param string $id
   *   The workspace id from Tripolis.
   *
   * @return mixed
   *   Article array or FALSE on failure.
   */
  public function getByNewsletterId($id) {
    if (!empty($id)) {
      try {
        // Fetch list of articles (meta data).
        $param = array('getByNewsletterIdRequest' => array('newsletterId' => $id));
        if ($result = $this->pagedSoapCall('getByNewsletterId', $param, 200)) {
          if (!is_array($result)) {
            $this->throwError($result);
          }
          else {
            // Fetch articles per piece.
            $articles = array();
            foreach ($result['articles']['article'] as $article_meta) {
              if ($article = $this->getById($article_meta['id'])) {
                $articles[] = $article;
              }
            }
            return $articles;
          }
        }
      }
      catch (SoapFault $e) {
        $this->throwError($e);
      }
    }
    return FALSE;
  }

  /**
   * Create article.
   *
   * @param array $content
   *   Fields with values.
   * @param bool $auto_increment_label
   *   Try to increment label if it already exists
   *   and Tripolis refuses to create the article.
   *
   * @return array|bool
   *   Return array (id), or FALSE on failure.
   */
  public function create(array $content, $auto_increment_label = TRUE) {
    try {
      // Set field values.
      $field_values = array();
      foreach ($content['fields'] as $key => $value) {
        $field_values[] = array(
          'key'   => $key,
          'value' => $value,
        );
      }

      // Set soap params.
      $param = array(
        'createRequest' => array(
          'articleTypeId'      => $content['articleTypeId'],
          'label'              => $this->trimLength($content['label']),
          'name'               => $this->trimLength($content['name']),
          'articleTagIds'      => $content['articleTagIds'],
          'articleFieldValues' => array(
            'articleFieldValue' => $field_values,
          ),
        ),
      );
      $result = $this->call('create', $param);

      // Try to auto-increment article label/name
      // in case label or name already exists.
      if ($auto_increment_label) {
        $counter = 1;
        $retry = TRUE;
        while ($retry) {
          $retry = FALSE;
          // See if result is error and contains 'label/name already exists'.
          if (is_object($result) && is_a($result, 'SoapFault')) {
            if (isset($result->detail->errorResponse->errors->error)) {
              $error = $result->detail->errorResponse->errors->error;

              // Check label and name field.
              if ($error->errorCode == '401') {
                if (($error->identifierName == 'label' &&
                    $error->message == 'label already exists') ||
                  ($error->errorCode == '401' &&
                    $error->identifierName == 'name' &&
                    $error->message == 'name already exists')
                ) {

                  // Execute next cycle, to check and resend.
                  $retry = TRUE;

                  // Increment label.
                  $suffix = ' (' . $counter . ')';
                  $string_len = 40 - strlen($suffix);
                  $param['createRequest']['label'] = $this->trimLength($content['label'], $string_len) . $suffix;

                  // Increment name.
                  $suffix = '_' . $counter;
                  $string_len = 40 - strlen($suffix);
                  $param['createRequest']['name'] = $this->trimLength($content['name'], $string_len) . $suffix;

                  // Call API again.
                  // Result will be checked next cycle.
                  $result = $this->call('create', $param);
                  $counter++;
                }
              }
            }
          }
        }
      }

      return $result;
    }
    catch (SoapFault $e) {
      $this->throwError($e);
    }

    return FALSE;
  }

  /**
   * Create tag or get id from existing one.
   *
   * @param string $tag
   *   Tag name.
   *
   * @return mixed
   *   ID (string), of FALSE on failure.
   */
  public function createTag($tag) {
    // Set soap params.
    $param = array(
      'createTagRequest' => array(
        'workspaceId' => $this->workspaceId,
        'tag'         => strtolower($tag),
      ),
    );
    $result = $this->call('createTag', $param);

    // New tag created ?
    if (is_array($result) && isset($result['id'])) {
      debug($result['id'], 'tag ID');
      return $result['id'];
    }
    else {
      $error_response = json_decode(json_encode($result), TRUE);

    }

    debug($error_response, 'faulty tag!');
    if (isset($error_response['detail']['errorResponse']['errors']['error']['identifierId'])) {
      // Tag already exists.
      $id = $error_response['detail']['errorResponse']['errors']['error']['identifierId'];
      return $id;
    }

    return FALSE;
  }

}

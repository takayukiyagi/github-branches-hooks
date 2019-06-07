<?php

/**
 * https://github.com/takayukiyagi/github-branches-hooks
 */

$commands = array(
  'develop' => 'git pull 2>&1',
  'master'  => 'git pull 2>&1' 
);

/**
 * 設定
 */
date_default_timezone_set('Asia/Tokyo');
define( 'LOG_FILE', dirname( __FILE__ ) . '/hook.log' );
define( 'SECRET_KEY', 'GitHubのWebhooksで設定するSecretフィールドの値' );
$post_data = file_get_contents( 'php://input' );
$hmac      = hash_hmac( 'sha1', $post_data, SECRET_KEY );
$payload   = json_decode( $post_data, true );

/**
 * 認証＆処理実行
 */
if ( isset( $_SERVER['HTTP_X_HUB_SIGNATURE'] ) && $_SERVER['HTTP_X_HUB_SIGNATURE'] === 'sha1=' . $hmac ) {

  foreach ( $commands as $branch => $command ) {
    /**
     * ブランチ判断
     */
    if ( $payload['ref'] == 'refs/heads/' . $branch ) {
      if ( $command !== '' ) {
        /**
         * コマンド実行
         */
        exec( $command, $output, $return );
        file_put_contents( LOG_FILE,
          date( "【Y-m-d H:i:s】" ) . " " .
          "Connect to " . $_SERVER['REMOTE_ADDR'] . " ／ " .
          "branch: " . $branch . " ／ " .
          "commit message: " . $payload['commits'][0]['message'] . " ／ " .
          "output: " . $output[0] . " ／ " .
          "return: " . $return . "\n",
          FILE_APPEND | LOCK_EX
        );
      }
    }
  }
} else {
  /**
   * 認証失敗
   */
  file_put_contents( LOG_FILE,
    date( "【Y-m-d H:i:s】" ) .
    " Invalid access to " .
    $_SERVER['REMOTE_ADDR'] . "\n",
    FILE_APPEND | LOCK_EX
  );
}

?>
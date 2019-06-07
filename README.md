

GitHubのPush時などをフックし「ブランチごとに任意のコマンドを実行するプログラム（[deploy.php](https://github.com/takayukiyagi/github-branches-hooks/blob/master/deploy.php)）」です。

Webhooks [Payloads](https://developer.github.com/webhooks/#payloads) で認証を行い、Webhooksを通した場合のみコマンド実行されます。  
deploy.php を直接実行した場合は Invalid となります。  
セキュリティ関連は自己責任にてご了承ください。





# Usage



## 1. deploy.phpの編集・配置

配列 `$commands` にブランチ名（key）、実行コマンド（value）のペアを任意に設定。


```php
$commands = array(
  'develop' => 'git pull 2>&1',
  'master'  => 'git pull 2>&1' 
);
```

※ 上記の場合、master, developブランチ共に `git pull` を実行します。  
　ローカル環境からGitHubへ `git push` し、自動でリモート環境へデプロイさせたい場合に便利です。

シークレットキーの値は任意に書き換えてください。


```php
define( 'SECRET_KEY', 'GitHubのWebhooksで設定するSecretフィールドの値' );
```

編集を終えた deploy.php を、プログラム実行させたいリモート環境に設置。  
所有者は `apache` 等、任意のWebサーバーから実行可能にします。




## 2. GitHubとリモート環境のSSH接続

リモート環境には予めGitをインストールしておきます。

リモート環境上に任意の鍵ファイル名でSSHキーを生成します（パスフレーズなし）。  
`.ssh/config` ファイルにgithub.comの接続先情報を設定。  

```
Host github
HostName github.com
IdentityFile /{鍵の設置場所}/.ssh/{任意の鍵ファイル名}
User git
```

[SSH keys](https://github.com/settings/keys) 画面にて、生成した `{任意の鍵ファイル名}.pub` の内容を登録。  
リモート環境上で `ssh -T github` コマンドを実行、「Hi…」と返ればSSH接続成功です。






## 3. GitHubにWebhooksを登録

登録画面から「Add webhook」を押下、以下項目を登録。  
（`https://github.com/{ユーザー名}/{リポジトリ名}/settings/hooks`）


- Payload URL： `https://{リモート環境}/deploy.php`
- Content type： `application/json`
- Secret： `任意に設定したシークレットキー`
- Which events would you like to trigger this webhook?： `Just the push event.`
- [x] Active





## 4. 確認

以下テストしてみて予期した結果になるか確認。

- GitHubへPush（手動）→
- GitHub Webhooksがキャッチ（自動）→
- リモート環境のdeploy.phpを実行（自動）→
- ブランチごとに任意のコマンド実行（自動）


ログは `https://{リモート環境}/hook.log` を参照してください。  
以下のような結果が出力されます。

---
### Success

【yyyy-mm-dd 00:00:00】 Connect to `{GitHubホスト名}` ／ branch: `{ブランチ名}` ／ commit message: `{コミットメッセージ}` ／ output: `{exec戻り値$output}` ／ return: `{exec戻り値$return}`.

### Invalid

【yyyy-mm-dd 00:00:00】 Invalid access to `{外部ホスト名}`.

---

`{exec戻り値$return}` が `0` の場合は成功、それ以外の場合はコマンド処理が何らかの原因で失敗しています。

`{exec戻り値$output}` に『Host Key Verification..』とある場合、SSH接続に問題がある場合があります。  
リモート環境から一度でもGitHubに接続した場合は `.ssh/known_hosts` ファイルにGitHubのノードが含まれます。  
ノードが含まれていない場合、上記エラーが表示される場合があります。

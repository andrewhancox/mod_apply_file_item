<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'apply', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   mod_apply
 * @copyright Fumi.Iseki {@link http://www.nsl.tuis.ac.jp}, 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['messageprovider:submission'] = '申請書の提出';
$string['messageprovider:message']    = 'メッセージ';
$string['messageprovider:processed']  = '処理完了';

$string['accept_entry'] = '受理';
$string['acked_accept'] = '受理';
$string['acked_notyet'] = '未処理';
$string['acked_reject'] = '不受理';
$string['add_item']  = '項目を追加する';
$string['add_items'] = '項目を追加する';
$string['add_pagebreak'] = '依存用改ページを追加する';
$string['adjustment'] = '表示方向';
$string['apply_is_not_ready'] = 'まだ準備ができていません．最初に項目を編集してください．';
$string['apply:addinstance'] = '新しい申請フォームを追加する';
$string['apply:applies'] = '申請を提出する';
$string['apply:createprivatetemplate'] = 'プライベートテンプレートを作成する';
$string['apply:createpublictemplate'] = 'パブリックテンプレートを作成する';
$string['apply:deletetemplate'] = 'テンプレートの削除';
$string['apply:deletesubmissions'] = '書類の削除';
$string['apply:edititems'] = 'アイテムの編集';
$string['apply:edittemplates'] = 'テンプレートの編集';
$string['apply:mapcourse'] = 'コースをグローバル申請フォームにマップする';
$string['apply:operatesubmit'] = '認証操作';
$string['apply:preview'] = 'プレビュー';
$string['apply:receivemail'] = 'メール通知を受信する';
$string['apply:submit'] = '申請書の提出';
$string['apply:preview_submit'] = '申請画面のプレビュー';
$string['apply:view'] = '概要';
$string['apply:viewentries'] = '申請書の表示';
$string['apply:viewanalysepage'] = '回答送信後，分析ページを表示する';
$string['apply:viewreports'] = 'レポートを表示する';
$string['apply_is_already_submitted'] = '申請済み';
$string['apply_is_closed'] = '申請期間は終了しました';
$string['apply_is_disable']  = '貴方はこの申請を行う事はできません';
$string['apply_is_not_open'] = '申請受付はまだ開始されていません';
$string['apply_options'] = '申請フォームオプション';
$string['average'] = '平均';
$string['back_button'] = ' 戻る ';
$string['before_apply'] = '以前の書類';
$string['cancel_entry'] = '取消';
$string['cancel_entry_button'] = ' 取消し ';
$string['cancel_moving'] = '移動をキャンセルする';
$string['cannot_save_templ'] = 'テンプレートを保存することはできません';
$string['captcha'] = 'Captcha';
$string['check'] = 'チェックボックス';
$string['checkbox'] = 'チェックボックス';
$string['class_cancel']  = '取消';
$string['class_draft']   = '下書';
$string['class_newpost'] = '新規';
$string['class_update']  = '更新';
$string['confirm_cancel_entry'] = '本当にこのエントリを取消してもよろしいですか?';
$string['confirm_delete_entry'] = 'エントリを取り下げてもよろしいですか?';
$string['confirm_delete_item'] = '本当にこの項目を削除してもよろしいですか?';
$string['confirm_delete_submit'] = '本当にこの申請を削除してもよろしいですか?';
$string['confirm_delete_template'] = '本当にこのテンプレートを削除してもよろしいですか?';
$string['confirm_rollback_entry'] = 'エントリを取り下げてもよろしいですか?';
$string['confirm_use_template'] = '本当にこのテンプレートを使用しますか?';
$string['count_of_nums'] = '桁数';
$string['creating_templates'] = 'これらの項目を新しいテンプレートとして保存する';
$string['delete_entry'] = '取下げ';
$string['delete_entry_button'] = ' 取下げ ';
$string['delete_item'] = '項目を削除する';
$string['delete_submit'] = '申請の削除';
$string['delete_template'] = 'テンプレートを削除する';
$string['delete_templates'] = 'テンプレートを削除する ...';
$string['depending'] = '依存関係';
$string['depending_help'] = '依存アイテムを使用して他のアイテムの値に依存するアイテムを表示することができます．
<br />
<strong>以下，使用例です．</strong>
<br />
<ul>
<li>最初に他のアイテムが値を依存することになるアイテムを作成してください．</li>
<li>次に改ページ (Page break) を追加してください．</li>
<li>そして，最初に作成したアイテムの値に依存するアイテムを追加してください．アイテム作成フォーム内の「依存アイテム」リストから依存アイテム，そして「依存値」テキストボックスに必要な
値を入力してください．</li>
</ul>
<strong>構造は次のようになります:</strong>
<ol>
<li>Item Q: あなたは自動車を所有していますか? A: yes/no</li>
<li>改ページ (Page break)</li>
<li>Item Q: あなたの自動車の色は何色ですか?
<br />
(このアイテムはアイテム1の値=yesに依存します)</li>
<li>Item Q: あなたはなぜ自動車を所有していないのですか?
<br />
 (このアイテムはアイテム1の値=noに依存します)</li>
<li>
 ... 他のアイテム</li>
</ol>';
$string['dependitem'] = 'アイテムに依存する';
$string['dependvalue'] = '値に依存する';
$string['description'] = '説明';
$string['display_button'] = ' 表示 ';
$string['do_not_analyse_empty_submits'] = '空の送信を無視する';
$string['dropdown'] = 'ドロップダウンリスト';
$string['edit_entry']   = '編集';
$string['edit_entry_button'] = ' 編集 ';
$string['edit_item']  = '申請書を編集する';
$string['edit_items'] = '項目の編集';

$string['email_entry'] = 'メール通知を行う';
$string['email_notification'] = '管理者に通知メールを送信する';
$string['email_notification_help'] = '有効にした場合，申請フォームの送信に関して管理者宛にメール通知されます';
$string['email_notification_user'] = 'ユーザに通知メールを送信できるようにする';
$string['email_notification_user_help'] = '有効にした場合，申請の処理に関して申請者宛にメール通知が可能になります';
$string['email_confirm_text'] = '

下記ページにて詳細を閲覧できます:
{$a->url}';
$string['email_confirm_html'] = '<br /><br /><a href="{$a->url}">このページ</a>&nbsp;で詳細を閲覧できます．';
$string['email_teacher'] = '{$a->username} が申請フォーム 「{$a->apply}」 を投稿しました．';
$string['email_user_done']   = '貴方の申請 「{$a->apply}」 の処理が完了しました．';
$string['email_user_accept'] = '貴方の申請 「{$a->apply}」 が受理されました．';
$string['email_user_reject'] = '貴方の申請 「{$a->apply}」 は不受理となりました．';
$string['email_user_other']  = '管理者が申請 「{$a->apply}」 を処理しました．';
$string['email_noreply'] = 'このメールは自動送信メールです．このメールには返信しないでください．';
//
$string['only_acked_accept'] = '受領のみ';
$string['only_acked_accept_help'] = '書類受理のみ．処理を行う必要がない場合に指定する．';  
$string['enable_deletemode'] = '削除モード';
$string['enable_deletemode_help'] = '承認者が申請書を削除できるようにします．<br />通常は安全のため，必ず "No" に設定しておいてください．';
$string['can_discard'] = '申請書の取消';
$string['can_discard_help'] = '提出した申請書の取り消しを有効にする．';   
$string['date_format']       = '日付データのフォーマット';
$string['date_format_default']  = '%y/%m/%d %H:%M';
$string['date_format_help']  = '日付表示のフォーマットを指定する．デフォルトは '.$string['date_format_default'];
$string['entries_list_title'] = '申請書類一覧';
$string['entry_saved'] = 'あなたの申請書が送信されました';
$string['entry_saved_draft'] = 'あなたの申請書は下書きとして保存されました';
$string['entry_saved_operation'] = 'リクエストは処理されました';
$string['execd_done']    = '処理済';
$string['execd_entry']  = '処理済';
$string['execd_notyet']  = '未処理';
$string['exist'] = '有り';
$string['export_templates'] = 'テンプレートをエクスポートする';
$string['hide_no_select_option'] = '「未選択」オプションを隠す';
$string['horizontal'] = '水平';
$string['import_templates'] = 'テンプレートをインポートする';
$string['info'] = '情報';
$string['infotype'] = '情報タイプ';
$string['item_label'] = 'ラベル';
$string['item_label_help'] = 'ラベルを英数字で指定する．<br /><br />
特殊なラベル<br />
<ul>
<li><strong>submit_title</strong>
<ul><li>テキストフィールド（短文回答）にこのラベルが付いた場合，申請書のタイトルとして扱われる．</li></ul>
</li>
<li><strong>submit_only</strong>
<ul><li>申請時のみに表示される項目．利用許諾などに使用する．</li></ul>
</li>
<li><strong>admin_reply</strong>
<ul><li>申請時にユーザには表示されないが，申請後の画面には表示される．管理者は編集可能なので，管理者からのコメントなどに用いる．</li></ul>
</li>
<li><strong>admin_only</strong>
<ul><li>管理者だけが，表示・編集可能な項目．管理者のメモなどに使用する．</li></ul>
</li>
</ul>';

$string['item_name'] = '項目名';
$string['items_are_required'] = 'アスタリスクが付けられた項目は入力必須項目です．';
$string['label'] = 'ラベル';
$string['maximal'] = '最大';
$string['modulename'] = '申請フォーム';
$string['modulename_help'] = '各種の簡単な申請書を作成し，ユーザに提出させることができます．';
$string['modulenameplural'] = '申請フォーム';
$string['move_here'] = 'ここに移動する';
$string['move_item'] = 'この質問を移動する';
$string['movedown_item'] = 'この質問を下げる';
$string['moveup_item'] = 'この質問を上げる';
$string['multichoice'] = '多肢選択';
$string['multichoice_values'] = '多肢選択';
$string['multichoicerated'] = '多肢選択 (評定)';
$string['multichoicetype'] = '多肢選択タイプ';
$string['multiple_submit'] = '複数申請';
$string['multiple_submit_help'] = 'ユーザは無制限で申請フォームを送信することができます';
$string['name'] = '名称';
$string['name_required'] = '名称を入力してください';
$string['next_page_button'] = ' 次のページ ';
$string['no_itemlabel'] = 'ラベルなし';
$string['no_itemname'] = '無題';
$string['no_items_available_yet'] = '質問はまだ設定されていません．';
$string['no_settings_captcha'] = 'CAPTCHAの設定は編集できません．';
$string['no_submit_data'] = '指定されたデータは存在しません';
$string['no_templates_available_yet'] = 'テンプレートはまだ利用できません．';
$string['no_title'] = 'No Title';
$string['not_selected'] = '未選択';
$string['not_exist'] = '無し';
$string['numeric'] = '数値回答';
$string['numeric_range_from'] = '開始数値';
$string['numeric_range_to'] = '終了数値';
$string['only_one_captcha_allowed'] = '1申請フォームあたり，1つのCAPTCHAのみ許可されています．';
$string['operate_is_disable']  = '貴方はこの操作を行う事はできません';
$string['operate_submit'] = '処理';
$string['operate_submit_button'] = ' 実行 ';
$string['operation_error_execd'] = '書類を受理しなければ，処理済にすることはできません';
$string['overview'] = '概要と申請';
$string['pagebreak'] = '依存用改ページ';
$string['pluginadministration'] = '申請フォーム管理';
$string['pluginname'] = '申請フォーム';
$string['position'] = 'ポジション';
$string['preview'] = 'プレビュー';
$string['preview_help'] = 'このプレビューにて，あなたは質問の順番を変更することができます．';
$string['previous_apply'] = '以前の書類';
$string['previous_page_button'] = ' 前のページ ';
$string['public'] = '公開';
$string['radio'] = 'ラジオボタン';
$string['radiobutton'] = 'ラジオボタン';
$string['radiobutton_rated'] = 'ラジオボタン (評定)';
$string['radiorated'] = 'ラジオボタン (評定)';
$string['reject_entry'] = '不受理';
$string['related_items_deleted'] = 'この問題に関する，すべてのユーザの申請も削除されます．';
$string['required'] = '必須';
$string['resetting_data'] = '申請フォームをリセットする';
$string['responsetime'] = '回答時間';
$string['returnto_course'] = 'コースへ戻る';
$string['rollback_entry'] = '取下げ';
$string['rollback_entry_button'] = ' 取下げ ';
$string['save_as_new_item'] = '新しい質問として保存する';
$string['save_as_new_template'] = '新しいテンプレートとして保存する';
$string['save_draft_button']  = ' 下書き保存 ';
$string['save_entry_button']  = ' 申請書を送信 ';
$string['save_item'] = '保存';
$string['saving_failed'] = '提出に失敗しました';
$string['saving_failed_because_missing_or_false_values'] = '値が入力されていないか，正しくないため，提出に失敗しました';
$string['separator_decimal'] = '.';
$string['separator_thousand'] = ',';
$string['show_all'] = '{$a} 個のデータ全てを表示する';
$string['show_perpage'] = '1ページあたりの表示数を {$a} にする';
$string['start'] = '開始';
$string['started'] = '開始済み';
$string['stop'] = '終了';
$string['subject'] = '件名';
$string['submit_form_button'] = ' 新規申請 ';
$string['submit_new_apply']   = '新規申請を行う';
$string['submitted'] = '送信';
$string['switch_item_to_not_required'] = '必須回答を取消する';
$string['switch_item_to_required'] = '必須回答にする';
$string['template_saved'] = 'テンプレートが保存されました．';
$string['templates'] = 'テンプレート';
$string['textarea'] = '長文回答';
$string['textarea_height'] = '行数';
$string['textarea_width'] = '幅';
$string['textfield'] = '短文回答';
$string['textfield_maxlength'] = '最大文字数';
$string['textfield_size'] = 'テキストフィールド幅';
$string['outside_style'] = '項目の外枠のスタイル';
$string['outside_style_default'] = 'border: 0px solid';
$string['outside_style_help'] = '項目を囲む枠のスタイル．この設定はテーブル内では無視される．デフォルトは ( '.$string['outside_style_default'].' )';
$string['item_style'] = '項目のスタイル';
$string['item_style_default'] = '';
$string['item_style_help'] = '項目のスタイル．デフォルトは ( '.$string['item_style_default'].' )';
$string['time_close']  = '終了日時';
$string['time_close_help'] = 'あなたはユーザが書類提出のため申請フォームにアクセスできないようになる日時を指定することができます．チェックボックスがチェックされない場合，制限は定義されません．';
$string['time_open']   = '開始日時';
$string['time_open_help']  = 'あなたはユーザが書類提出のため申請フォームにアクセスできるようになる日時を指定することができます．チェックボックスがチェックされない場合，制限は定義されません．';
$string['title_ack']   = '受付';
$string['title_before'] = '以前の書類';
$string['title_check'] = 'チェック';
$string['title_class'] = '区分';
$string['title_draft'] = '下書';
$string['title_exec']  = '処理';
$string['title_title'] = 'タイトル';
$string['title_version'] = 'Ver.';
$string['update_entry'] = '更新';
$string['update_entry_button'] = ' 更新 ';
$string['update_item'] = '質問の変更を保存する';
$string['use_calendar'] = 'カレンダーに登録';
$string['use_calendar_help'] = '申請書の提出期間をカレンダーに登録できます';
$string['use_item'] = '{$a} を使用する';
$string['use_one_line_for_each_value'] = '1行に1つの値を入力してください!';
$string['use_this_template'] = 'このテンプレートを使用する';
$string['user_pic']      = '画像';
$string['username_manage'] = 'ユーザ名管理';
$string['username_manage_help'] = '表示される名前のパターンを選択できます';
//$string['firstname'] = get_string('firstname');
//$string['lastname']  = get_string('lastname');
$string['firstlastname'] = get_string('firstname').' '.get_string('lastname');
$string['lastfirstname'] = get_string('lastname').' '.get_string('firstname');
$string['using_templates'] = 'テンプレートの使用';
$string['vertical'] = '垂直';
$string['view_entries'] = '申請書の表示';
$string['wiki_url'] = 'http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?mod_apply';
$string['yes_button'] = ' はい ';

$string['submit_num'] = '申請数';

// for new added items (1.3.0)
$string['fixedtitle'] = '固定タイトル';
$string['tablestart'] = 'テーブルの開始';
$string['tableend']   = 'テーブルの終了';
$string['no_table']   = 'テーブルが開始されていません';
$string['nested_table'] = 'テーブルが入れ子になっています';
$string['not_close_table'] = 'テーブルが閉じられていない';
$string['table_columns'] = 'テーブルのカラム数';
$string['table_border'] = 'テーブル内部の罫線のサイズ';
$string['table_border_help'] = 'テーブルの枠の罫線サイズはこの値に +1 される';
$string['table_border_style'] = 'テーブル内部の罫線のタイプ';
$string['table_border_style_help'] = 'テーブルの枠の罫線のタイプは solid 固定';
$string['table_th_sizes'] = '各カラムの横幅 (px)';
$string['table_th_sizes_help'] = '各カラムの横幅(px単位) をカンマで区切って記述する. ';
$string['table_th_strings'] = '各カラムのタイトル';
$string['table_th_strings_help'] = '各カラムのタイトルを改行で区切って記述する．つまり，一行に一つタイトルを記述する．';
$string['table_disp_iname'] = '項目名の表示';
$string['table_disp_iname_help'] = 'テーブル内の項目の名前を表示する．';

//
$string['printpagebreak']  = '印刷用改ページを追加する';
$string['pagebreak_title'] = '印刷用改ページ';
$string['pagebreak_style'] = '印刷用ラインのスタイル';
$string['pagebreak_style_default'] = '1px solid';
$string['pagebreak_style_help'] = '印刷用改ページを示すラインのスタイルを指定する (&lt; hr /&gt;). デフォルトは ( '.$string['pagebreak_style_default'].' ). 何も指定しない場合，印刷用の改ページのラインは表示されない．なお，印刷用ラインはサブミット画面およにサブミット画面でのプレビューでは表示されない．';

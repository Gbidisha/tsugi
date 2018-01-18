<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\Git;
use \Tsugi\UI\HandleBars;

if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
session_start();
require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

$LAUNCH = LTIX::session_start();

// In case we need a setuid copy of git
if ( isset($CFG->git_command) ) {
    Git::set_bin($CFG->git_command);
}

// Cleanup partial git attempts
unset($_SESSION['git_results']);

$repo = new \Tsugi\Util\GitRepo($CFG->dirroot);
$git_version = $repo->run('--version');

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

require_once("../sanity-db.php");

if ( ! isset($CFG->install_folder) ) {
    echo('<h1>Install folder ($CFG->install_folder) is not configured</h1>'."\n");
    $OUTPUT->footer();
    return;
}

?>
<div id="readonly-dialog" title="Read Only Dialog" style="display: none;">
<p>This server does not appear to allow the <b>git</b> command to
make changes to the web files.  So this tool will not be able to install,
update, or reconfigure any of these tools.  You will have to update these files
some other way.  Alternatively, you may be able to configure a copy of <b>git</b>
that can update the file system - see the documentation for <b>$CFG->git_command</b> in
the <b>config-dist.php</b> file.
</p>
</div>
<div id="iframe-dialog" title="Read Only Dialog" style="display: none;">
   <iframe name="iframe-frame" style="height:400px" id="iframe-frame"
    src="<?= $OUTPUT->getSpinnerUrl() ?>"></iframe>
</div>
<p>This screen is a wrapper for the <b>git</b> command if it is installed in your system.
This screen runs <b>git</b> commands on your behalf.
It will only handle the normal operations and assume that they work.  If you log in
and edit the files after they are checked out, this tool might not be able to upgrade
some of these git repos.
</p>
<p>Using: <?= htmlentities($git_version) ?></p>
<ul class="nav nav-tabs">
  <li class="active"><a href="#home" data-toggle="tab" aria-expanded="true">Installed Modules</a></li>
<?php if(isset($CFG->lessons)) { ?>
  <li class=""><a href="#required" data-toggle="tab" aria-expanded="false">Required Modules</a></li>
<?php } ?>
  <li class=""><a href="#available" data-toggle="tab" aria-expanded="false">Available Modules</a></li>
  <li class=""><a href="#advanced" data-toggle="tab" aria-expanded="false">Advanced</a></li>
</ul>
<div id="myTabContent" class="tab-content" style="margin-top:10px;">
  <div class="tab-pane fade active in" id="home">
    <ul id="installed_ul">
    <img src="<?= $OUTPUT->getSpinnerUrl() ?>" id="spinner">
    </ul>
  </div>
<?php if(isset($CFG->lessons)) { ?>
  <div class="tab-pane fade" id="required">
    <ul id="required_ul">
    <img src="<?= $OUTPUT->getSpinnerUrl() ?>" id="spinner">
    </ul>
  </div>
<?php } ?>
  <div class="tab-pane fade" id="available">
    <ul id="available_ul">
    <img src="<?= $OUTPUT->getSpinnerUrl() ?>" id="spinner">
    </ul>
  </div>
  <div class="tab-pane fade" id="advanced">
    <p>This screen allows you to clone a repository into your <b>install_folder</b>.
    Make sure to know the code you are installing and review it carefully before
    installing it. The repository will be checked out into a folder of the
    same name as the respsitory.</p>
    <p>
    <form method="GET" action="git.php" target="iframe-frame">
    <input type="hidden" name="command" value="clone">
    Repository: <input size="60" type="text" name="remote"><br/>
    <!-- Sub-Folder: <input type="text" name="folder"> (optional)<br/> -->
    <input type="submit" value="Clone Repository"
        onclick="showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);" >
    </form>
    </p>
  </div>
</div>

<?php



$OUTPUT->footerStart();
HandleBars::templateInclude(array('installed', 'available'));
if(isset($CFG->lessons)) {
    HandleBars::templateInclude('required');
}
?>
<script>
$(document).ready(function(){
    $.getJSON('<?= addSession('repos_json.php') ?>', function(repos) {
        window.console && console.log(repos);
        tsugiHandlebarsToDiv('installed_ul', 'installed', repos);
<?php if(isset($CFG->lessons)) { ?>
        tsugiHandlebarsToDiv('required_ul', 'required', repos);
<?php } ?>
        tsugiHandlebarsToDiv('available_ul', 'available', repos);
    }).fail( function() { alert('getJSON fail'); } );
});

</script>
<?php
$OUTPUT->footerEnd();

<?php

namespace RJPlugins;

use Gdn_Plugin;
use Gdn;

// Add option to profile "Delete Account"
// Require re-entering password
class SelfDeletePlugin extends Gdn_Plugin {
    /*
    // maybe a better place for the self delete link, but it looks weird...
    public function profileController_editMyAccountAfter_handler($sender, $args) {
        echo '<li class="User-SelfDelete">',
            $sender->Form->label('Delete your account'),
            anchor('Delete Account', '/plugin/selfdelete', 'Button'),
            '</li>';
    }
    */

    /**
     * Add "Delete Account" option to edit profile menu.
     *
     * @param ProfileController $sender
     * @param mixed $args
     *
     * @return void.
     */
    public function profileController_afterAddSideMenu_handler($sender, $args) {
        // Only proceed if someone is editing his own profile.
        if (!$sender->EditMode) {
            return;
        }
        $args['SideMenu']->addLink(
            'Options',
            Gdn::translate('Delete Account'),
            '/plugin/selfdelete',
            'Plugins.SelfDelete.Allow',
            ['class' => 'self-delete']
        );
    }

    public function pluginController_selfDelete_handler($sender, $args) {
        $sender->permission('Plugins.SelfDelete.Allow');
        if ($sender->Form->authenticatedPostBack(true)) {
        }
        $sender->title(Gdn::translate('Delete your own account'));
        $sender->render('selfdelete', '', 'plugins/rj-self-delete');
    }
}

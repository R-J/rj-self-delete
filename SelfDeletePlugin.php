<?php

namespace RJPlugins;

use Gdn_Plugin;
use Gdn;

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
            '/profile/selfdelete',
            'Plugins.SelfDelete.Allow',
            ['class' => 'self-delete']
        );
    }

    /**
     * Form that requies authentication and will allow user to delete
     * (method "keep") their account.
     *
     * @param ProfileController $sender
     *
     * @return void.
     */
    public function profileController_selfDelete_create($sender) {
        $sender->permission('Plugins.SelfDelete.Allow');

        // Get user data.
        $sender->getUserInfo('', '', Gdn::session()->UserID);

        $sender->Form = new \Gdn_Form();
        $validation = new \Gdn_Validation();

        if ($sender->Form->authenticatedPostBack() == true) {
            $validation->applyRule('Email', ['Required', 'Email']);
            $validation->applyRule('Password', 'Required');
            $formValues = $sender->Form->formValues();
            $validation->validate($formValues);

            // Get user and ensure it is the session user.
            $user = $sender->UserModel->getByEmail($formValues['Email']);
            if (!$user || $user->UserID != Gdn::session()->UserID) {
                $validation->addValidationResult(
                    'Email',
                    'Authentication failed'
                );
            } else {
                // Check the password.
                $passwordHash = new \Gdn_PasswordHash();
                $passwordChecked = $passwordHash->checkPassword(
                    $formValues['Password'],
                    $user->Password,
                    $user->HashMethod
                );
                // Rate limiting.
                $sender->UserModel->rateLimit($user);
                if ($passwordChecked === true) {
                    // End session.
                    Gdn::session()->end();
                    // Delete user.
                    $sender->UserModel->deleteID($user->UserID, ['DeleteMethod' => 'keep']);
                    redirectTo('/');
                } else {
                    $validation->addValidationResult(
                        'Email',
                        'Authentication failed'
                    );
                }
            }
        }

        $sender->Form->setValidationResults($validation->results());

        $title = Gdn::translate('Delete Account');
        $sender->title($title);
        $sender->_setBreadcrumbs($title, $sender->canonicalUrl());
        $sender->render('selfdelete', '', 'plugins/rj-self-delete');
    }
}

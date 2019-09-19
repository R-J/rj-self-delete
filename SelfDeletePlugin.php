<?php

namespace RJPlugins;

use Gdn_Plugin;
use Gdn;

// TODO: Ensure UserModel->getByUserEmail includes deleted users
class SelfDeletePlugin extends Gdn_Plugin {
    public function setup() {
        $this->structure();
    }

    public function structure() {
        Gdn::config()->touch('Plugin.SelfDelete.UserNameScheme', '+ %s +');
    }

    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('self-delete.css', 'plugins/rj-self-delete');
    }

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
                if ($this->passwordCheckPassed($sender, $formValues['Password'], $user)) {
                    // End session and delete user.
                    Gdn::session()->end();
                    $this->deleteUser($user);
                    // Restore Username
                    $sender->UserModel->setField(
                        $user->UserID,
                        'Name',
                        sprintf(
                            Gdn::config('Plugin.SelfDelete.UserNameScheme', '+ %s +'),
                            $user->Name
                        )
                    );
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

    protected function passwordCheckPassed($sender, $password, $user) {
        // Check the password.
        $passwordHash = new \Gdn_PasswordHash();
        $passwordChecked = $passwordHash->checkPassword(
            $password,
            $user->Password,
            $user->HashMethod
        );
        // Rate limiting.
        $sender->UserModel->rateLimit($user);
        return $passwordChecked;
    }

    protected function deleteUser($sender, $user) {
        $sender->UserModel->deleteID(
            $user->UserID,
            ['DeleteMethod' => 'keep']
        );
        // Do some other clean up
        // e.g. Profile Extender fields
        // Reason for application
        // ...
    }

    public function userModel_beforeSaveValidation_handler($sender, $args) {
        $user = $sender->getByUsername(
            sprintf(
                Gdn::config('Plugin.SelfDelete.UserNameScheme', '+ %s +'),
                $args['FormPostValues']['Name']
            )
        );
        $sender->Validation->addValidationResult('Name', 'That name is taken by an already deleted user.');
    }
}

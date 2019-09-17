<?php defined('APPLICATION') or die ?>
<div class="FormTitleWrapper">
    <h1 class="H"><?= $this->data('Title') ?></h1>
    <?= $this->Form->open(), $this->Form->errors() ?>
    <ul>
        <li class="self-delete-warning">
            <div class="DismissMessage AlertMessage">
                <?php echo Gdn::translate('This will delete your account without any chance to recover it!') ?>
            </div>
        </li>
        <li class="self-delete-email">
            <?= $this->Form->label('Please enter your email address to validate this action', 'Email') ?>
            <?= $this->Form->textBox('Email') ?>
        </li>
        <li class="self-delete-password">
            <?= $this->Form->label('Please enter your password to validate this action', 'Password') ?>
            <?= $this->Form->input('Password', 'Password', ['type' => 'password']) ?>
        </li>
    </ul>
    <?= $this->Form->close('Delete Account', '', ['class' => 'Button Danger']) ?>
</div>

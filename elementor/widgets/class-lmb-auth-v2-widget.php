<?php
// FILE: elementor/widgets/class-lmb-auth-v2-widget.php

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_Auth_V2_Widget extends Widget_Base {
    public function get_name() {
        return 'lmb_auth_v2';
    }

    public function get_title() {
        return __('Authentification V2', 'lmb-core');
    }

    public function get_icon() {
        return 'eicon-lock-user';
    }

    public function get_categories() {
        // This widget can be used in any user-facing area
        return ['lmb-user-widgets-v2'];
    }

    public function get_script_depends() {
        return ['lmb-auth-v2'];
    }

    public function get_style_depends() {
        return ['lmb-auth-v2'];
    }

    protected function render() {
        ?>
        <div class="lmb-auth-v2-widget">
            <div class="lmb-auth-tabs">
                <button class="lmb-auth-tab-btn active" data-form="login">Connexion</button>
                <button class="lmb-auth-tab-btn" data-form="signup">Inscription</button>
            </div>
            <div class="lmb-auth-content">
                <!-- Formulaire de Connexion -->
                <form id="lmb-login-form" class="lmb-auth-form active">
                    <div class="lmb-form-response"></div>
                    <div class="lmb-form-group">
                        <label for="login-email">Nom d'utilisateur ou Email</label>
                        <input type="text" id="login-email" name="username" class="lmb-input" required>
                    </div>
                    <div class="lmb-form-group">
                        <label for="login-password">Mot de passe</label>
                        <input type="password" id="login-password" name="password" class="lmb-input" required>
                    </div>
                    <button type="submit" class="lmb-btn">Se connecter</button>
                </form>

                <!-- Formulaire d'Inscription -->
                <form id="lmb-signup-form" class="lmb-auth-form">
                    <div class="lmb-signup-toggle">
                        <button type="button" class="lmb-signup-toggle-btn active" data-type="regular">Client Régulier</button>
                        <button type="button" class="lmb-signup-toggle-btn" data-type="professional">Professional</button>
                    </div>

                    <div class="lmb-form-response"></div>
                    <input type="hidden" name="signup_type" value="regular">

                    <!-- Champs Client Régulier -->
                    <div id="lmb-signup-regular-fields">
                        <div class="lmb-form-group"><label for="signup-firstname">Prénom</label><input type="text" name="first_name" id="signup-firstname" class="lmb-input"></div>
                        <div class="lmb-form-group"><label for="signup-lastname">Nom</label><input type="text" name="last_name" id="signup-lastname" class="lmb-input"></div>
                        <div class="lmb-form-group"><label for="signup-phone-regular">Téléphone</label><input type="tel" name="phone_regular" id="signup-phone-regular" class="lmb-input"></div>
                        <div class="lmb-form-group"><label for="signup-city-regular">Ville</label><input type="text" name="city_regular" id="signup-city-regular" class="lmb-input"></div>
                    </div>
                    <!-- Champs Client Professionnel (initialement cachés) -->
                    <div id="lmb-signup-professional-fields" style="display: none;">
                        <div class="lmb-form-group"><label for="signup-company-name">Nom de la Société</label><input type="text" name="company_name" id="signup-company-name" class="lmb-input"></div>
                        <div class="lmb-form-group"><label for="signup-company-hq">Adresse du Siège Social</label><input type="text" name="company_hq" id="signup-company-hq" class="lmb-input"></div>
                        <div class="lmb-form-group"><label for="signup-company-city">Ville</label><input type="text" name="city_professional" id="signup-company-city" class="lmb-input"></div>
                        <div class="lmb-form-group"><label for="signup-company-rc">RC</label><input type="text" name="company_rc" id="signup-company-rc" class="lmb-input"></div>
                        <div class="lmb-form-group"><label for="signup-company-phone">Téléphone</label><input type="tel" name="phone_professional" id="signup-company-phone" class="lmb-input"></div>
                    </div>
                    
                    <!-- Champs Communs -->
                    <div class="lmb-form-group"><label for="signup-email">Email</label><input type="email" name="email" id="signup-email" class="lmb-input"></div>
                    <div class="lmb-form-group"><label for="signup-password">Mot de passe</label><input type="password" name="password" id="signup-password" class="lmb-input"></div>
                    
                    <button type="submit" class="lmb-btn">Créer un Compte</button>
                </form>
            </div>
        </div>
        <?php
    }
}
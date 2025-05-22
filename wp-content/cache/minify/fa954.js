!function(n,i,e){var m=!1;n(i).on("mouseenter","#wp-admin-bar-imagify",function(){var a,i;!0!==m&&(m=!0,(a=n("#wp-admin-bar-imagify-profile-content")).is(":empty"))&&(i=e.ajaxurl||e.imagifyAdminBar.ajaxurl,i+=0<i.indexOf("?")?"&":"?",n.get(i+"action=imagify_get_admin_bar_profile&imagifygetadminbarprofilenonce="+n("#imagifygetadminbarprofilenonce").val()).done(function(i){a.html(i.data),n("#wp-admin-bar-imagify-profile-loading").remove(),m=!1}))})}(jQuery,document,window);
;/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
var __webpack_exports__ = {};


class elementorHelloThemeHandler {
  constructor() {
    this.initSettings();
    this.initElements();
    this.bindEvents();
  }
  initSettings() {
    this.settings = {
      selectors: {
        menuToggle: '.site-header .site-navigation-toggle',
        menuToggleHolder: '.site-header .site-navigation-toggle-holder',
        dropdownMenu: '.site-header .site-navigation-dropdown'
      }
    };
  }
  initElements() {
    this.elements = {
      window,
      menuToggle: document.querySelector(this.settings.selectors.menuToggle),
      menuToggleHolder: document.querySelector(this.settings.selectors.menuToggleHolder),
      dropdownMenu: document.querySelector(this.settings.selectors.dropdownMenu)
    };
  }
  bindEvents() {
    var _this$elements$menuTo;
    if (!this.elements.menuToggleHolder || (_this$elements$menuTo = this.elements.menuToggleHolder) !== null && _this$elements$menuTo !== void 0 && _this$elements$menuTo.classList.contains('hide')) {
      return;
    }
    this.elements.menuToggle.addEventListener('click', () => this.handleMenuToggle());
    this.elements.menuToggle.addEventListener('keyup', event => {
      const ENTER_KEY = 13;
      const SPACE_KEY = 32;
      if (ENTER_KEY === event.keyCode || SPACE_KEY === event.keyCode) {
        event.currentTarget.click();
      }
    });
    this.elements.dropdownMenu.querySelectorAll('.menu-item-has-children > a').forEach(anchorElement => anchorElement.addEventListener('click', event => this.handleMenuChildren(event)));
  }
  closeMenuItems() {
    this.elements.menuToggleHolder.classList.remove('elementor-active');
    this.elements.window.removeEventListener('resize', () => this.closeMenuItems());
  }
  handleMenuToggle() {
    const isDropdownVisible = !this.elements.menuToggleHolder.classList.contains('elementor-active');
    this.elements.menuToggle.setAttribute('aria-expanded', isDropdownVisible);
    this.elements.dropdownMenu.setAttribute('aria-hidden', !isDropdownVisible);
    this.elements.menuToggleHolder.classList.toggle('elementor-active', isDropdownVisible);

    // Always close all sub active items.
    this.elements.dropdownMenu.querySelectorAll('.elementor-active').forEach(item => item.classList.remove('elementor-active'));
    if (isDropdownVisible) {
      this.elements.window.addEventListener('resize', () => this.closeMenuItems());
    } else {
      this.elements.window.removeEventListener('resize', () => this.closeMenuItems());
    }
  }
  handleMenuChildren(event) {
    const anchor = event.currentTarget;
    const parentLi = anchor.parentElement;
    if (!(parentLi !== null && parentLi !== void 0 && parentLi.classList)) {
      return;
    }
    parentLi.classList.toggle('elementor-active');
  }
}
document.addEventListener('DOMContentLoaded', () => {
  new elementorHelloThemeHandler();
});
/******/ })()
;
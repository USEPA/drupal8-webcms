// Private Sitewide Alert script
import Drupal from 'drupal';

(function(Drupal) {
  Drupal.behaviors.sitewideAlertPrivate = {
    attach(context) {
      const privateMedia = document.getElementsByClassName('js-media-private');
      const privateMediaCount = privateMedia.length;

      if (privateMediaCount > 0) {
        const nodeHTML = document.querySelector('body');
        const dataAlert = document.title.replace(/\s+/g, '-').toLowerCase();

        const privateMediaAlert = `<div class="usa-site-alert usa-site-alert--has-heading usa-site-alert--private js-sitewide-alert" data-alert="${dataAlert}">
          <div class="usa-alert">
            <div class="usa-alert__body">
              <div class="u-visually-hidden">Notice</div>
              <div class="usa-alert__content">
                <h3 class="usa-alert__heading">This page contains <span id="js-private-media-count">${privateMediaCount}</span> media files that are marked private.</h3>
                <div class="usa-alert__text">
                  <p>These files will not be available to your users. To correct, please do this... lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempot incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud.</p>
                </div>
              </div>
            </div>
          </div>
        </div>`;

        nodeHTML.innerHTML = nodeHTML.innerHTML + privateMediaAlert;
      }
    },
  };
})(Drupal);

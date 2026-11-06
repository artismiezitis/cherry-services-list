/* npm install --prefix <test-tools> jsdom@26 jquery@3.7.1
 * NODE_PATH=<test-tools>/node_modules node tests/frontend.cjs
 * DOM tests; this does not replace visual browser testing.
 */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM } = require('jsdom');
const jquery = require('jquery');

async function check(elementor) {
  const listing = (id) => `<section id="${id}" class="services-container">
    <div class="cherry-services" data-page="1" data-pages="3" data-atts='{"limit":2}'>
      <ul class="cherry-services-filter"><li class="active"><a class="cherry-services-filter_link" data-term="all-cats" href="#">All</a></li><li><a class="cherry-services-filter_link" data-term="cat" href="#">Category</a></li></ul>
      <div class="services-listing">Initial</div>
      <a class="ajax-more-btn" href="#">More</a>
      <div class="services-ajax-pager"><a class="page-numbers" data-page="2" href="#">2</a></div>
    </div></section>`;
  const dom = new JSDOM(listing('one') + listing('two'), { runScripts: 'outside-only', url: 'http://example.invalid' });
  const { window } = dom;
  const $ = jquery(window);
  window.jQuery = $;
  window.cherryServices = { loader: 'Loading', ajaxurl: '/wp-admin/admin-ajax.php' };
  window.CherryJsCore = { status: { is_ready: false }, variable: { $document: $(window.document) } };
  if (elementor) window.elementorFrontend = { hooks: { addAction() {} }, isEditMode: () => false };
  const requests = [];
  $.ajax = (options) => {
    requests.push({ ...options.data });
    return { done(callback) {
      callback({ success: true, data: {
        result: `<article>Response ${requests.length}</article>`, atts: { limit: 2 }, page: 2, pages: 3,
        pager: '<div class="services-ajax-pager"><a class="page-numbers" data-page="2" href="#">2</a></div>'
      } });
      return this;
    } };
  };
  window.eval(fs.readFileSync(path.join(__dirname, '../public/assets/js/cherry-services.js'), 'utf8'));
  await new Promise((resolve) => $(resolve));
  $('#two .cherry-services-filter_link[data-term="cat"]').trigger('click');
  assert.equal(requests.length, 1, 'Second listing filter did not initialize');
  assert.equal(requests[0].action, 'cherry_services_filter_posts');
  assert.match($('#one .services-listing').text(), /Initial/, 'Other listing was modified');
  $('#one .ajax-more-btn').trigger('click');
  $('#two .ajax-more-btn').trigger('click');
  assert.equal(requests.length, 3, 'Each listing needs its own More handler');
  $('#two .services-ajax-pager a').trigger('click');
  assert.equal(requests.length, 4);
  assert.equal(requests[3].action, 'cherry_services_pager');
  assert.equal(requests[3].page, 2);
  assert.equal($('.services-loader').length, 0, 'Loader left behind');
  window.close();
}
(async () => {
  await check(false);
  await check(true);
  console.log('PASS JavaScript: ready event, multiple listings, filters, More, pagination, Elementor coexistence');
})().catch((error) => { console.error(error); process.exitCode = 1; });

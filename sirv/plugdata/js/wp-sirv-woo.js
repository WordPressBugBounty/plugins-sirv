jQuery(function ($) {

  let existingIds = [];
  let itemByVariationId = {};
  let $instance = null;
  let galleryId = null;
  let placeholderType = 'none';
  let thumbnailsPosition = 'bottom';

  const pdpId = sirv_woo_product.mainID;
  const variationStatus = sirv_woo_product.variationStatus;
  const captionBlockSelector = `.sirv-woo-smv-caption_${pdpId}`;
  const fullScreenCaptionBlockSelector = `.sirv-woo-smv-fullscreen-caption_${pdpId}`;


  function filterByGroups(id = '') {
    if (!!!$instance) return;

    id = id * 1;

    if(!!id && inArray(id, existingIds)){
      id = id + '';
      $instance.switchGroup(id);
    }else{
      $instance.switchGroup('main');
    }

    $instance.jump(0);

    updateCaption(pdpId);
  }


  function inArray(val, arr){
    return arr.indexOf(val) !== -1;
  }


  function initializeCaption(){
    const isCaption = $(`#sirv-woo-gallery_data_${pdpId}`).attr('data-is-caption');

    if(!!isCaption){
      const caption = getSlideCaption(pdpId);

      if (!$(fullScreenCaptionBlockSelector).length) {
        $(`#sirv-woo-gallery_${pdpId} .smv-slides-box`).after(
          `<div class="sirv-woo-smv-caption sirv-woo-smv-fullscreen-caption_${pdpId}">${caption}</div></div>`,
        );
      }

      if (placeholderType === 'image') {
        $(captionBlockSelector).show();
      } else {
        if (inArray(thumbnailsPosition, ['bottom', 'top']))
        $(fullScreenCaptionBlockSelector).show();
      }
    }
  }


  function getSlideCaption(id){
    let $caption;

    if(!!galleryId){
      $caption = $($(`#${galleryId} .smv-slide.smv-shown .smv-content div, #${galleryId} .smv-slide.smv-shown .smv-content img`)[0]);
    }else{
      $caption = $($(`#sirv-woo-gallery_${id} .smv-slide.smv-shown .smv-content div, #sirv-woo-gallery_${id} .smv-slide.smv-shown .smv-content img`)[0]);
    }

    return $caption.attr('data-slide-caption') || '';
  }


  function updateCaption(id){
    const caption = getSlideCaption(id);

    $(captionBlockSelector).html(caption);
    $(fullScreenCaptionBlockSelector).html(caption);
  }


  function getJSONData(key, type) {
    let data = type === 'object' ? {} : [];
    const idsJsonStr = $(`#sirv-woo-gallery_data_${pdpId}`).attr(key);
    try {
      data = JSON.parse(idsJsonStr);
    } catch (error) {
      console.error(error);
    }

    return data;
  }


  function showVariation(variation_id){
    if (!!variation_id) {
      if (variationStatus !== "allByVariation") {
        filterByGroups(variation_id);
      } else if (variationStatus === "allByVariation" && !!itemByVariationId[variation_id]) {
        $instance.jump(itemByVariationId[variation_id]);
      }
    } else {
      if (variationStatus === "all") {
        filterByGroups();
      } else {
        filterByGroups(pdpId);
      }
    }
  }


  $(document).ready(function () {
    placeholderType = $(`#sirv-woo-gallery_data_${pdpId}`).attr('data-gallery-placeholder-type');
    thumbnailsPosition = $(`#sirv-woo-gallery_data_${pdpId}`).attr(
      "data-thumbnails-position",
    );
    existingIds = getJSONData("data-existings-ids", "array");

    itemByVariationId = getJSONData("data-item-by-variation-id", "object");

    $( ".single_variation_wrap" ).on( "show_variation", function ( event, variation ) {
    let variation_id = variation.variation_id || '';
      showVariation(variation_id);
    });

  /*//fire on select change
    $( '.variations_form' ).on( 'woocommerce_variation_select_change', function(event) {
      //code here
    }); */

    $('.reset_variations').on('click', function () {
      filterByGroups();
    });

    //fix for avada variation switcher
    $("body").on("reset_data", ".variations_form", function () {
      filterByGroups();
    });


    Sirv.on('viewer:ready', function (viewer) {
      $('.sirv-skeleton').removeClass('sirv-skeleton');
      $('.sirv-woo-opacity-zero').addClass('sirv-woo-opacity');

      $instance = Sirv.viewer.getInstance('#sirv-woo-gallery_' + pdpId);
      galleryId = $(`#sirv-woo-gallery_${pdpId} div.smv`).attr('id');

      let curVariantId = $("input.variation_id").val() * 1;
      if(curVariantId > 0){
        if (variationStatus === "allByVariation"){
          filterByGroups();
        }
          showVariation(curVariantId);
      }

      initializeCaption();

      var opacityTimer = setTimeout(function(){
        $(".sirv-pdp-gallery-placeholder").css("opacity", "0");
        clearTimeout(opacityTimer);
      }, 800);
    });


    Sirv.on("viewer:fullscreenIn", function(viewer){
      if (placeholderType === "image" || inArray(thumbnailsPosition, ['right', 'left'])) {
        $(fullScreenCaptionBlockSelector).show();
      }
    });


    Sirv.on("viewer:fullscreenOut", function(viewer){
      if (placeholderType === "image" || inArray(thumbnailsPosition, ['right', 'left'])) {
        $(fullScreenCaptionBlockSelector).hide();
      }
    });


    Sirv.on('viewer:afterSlideIn', function(slide){
        updateCaption(pdpId);
    });

  }); //end dom ready
}); // end closure

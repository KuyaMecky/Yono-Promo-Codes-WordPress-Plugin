(function($){
  $(document).on('click', '.yg-media-select', function(e){
    e.preventDefault();
    var $btn = $(this);
    var $input = $($btn.data('target'));
    var $preview = $($btn.data('preview'));
    var $remove = $btn.siblings('.yg-media-remove');

    var frame = wp.media({
      title: 'Select or Upload Logo',
      button: { text: 'Use this image' },
      multiple: false
    });

    frame.on('select', function(){
      var att = frame.state().get('selection').first().toJSON();
      var url = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
      $input.val(url).trigger('change');
      $preview.attr('src', url).show();
      $remove.show();
    });

    frame.open();
  });

  $(document).on('click', '.yg-media-remove', function(e){
    e.preventDefault();
    var $btn = $(this);
    var $input = $($btn.data('target'));
    var $preview = $($btn.data('preview'));
    $input.val('').trigger('change');
    $preview.hide().attr('src','');
    $btn.hide();
  });

  $(document).on('input change', '#yg_logo', function(){
    var url = $(this).val().trim();
    var $wrap = $(this).closest('p');
    var $preview = $wrap.find('.yg-media-preview');
    var $remove = $wrap.find('.yg-media-remove');
    if (url){ $preview.attr('src', url).show(); $remove.show(); }
    else { $preview.hide().attr('src',''); $remove.hide(); }
  });
})(jQuery);

/* globals jQuery, window */

(function ($) {
    /* -------------------------------------------------------------
     *  1. Builder tab – Add/Remove custom URLs
     * ----------------------------------------------------------- */
    $(document).on('click', '.acsp-add-url', function () {
        const dir = $(this).data('dir');
        const html =
            '<div style="margin-top:4px;">' +
            '<input type="text" name="acsp_policy[' + dir + '][]" value="" ' +
            'placeholder="https://example.com " class="regular-text code" /> ' +
            '<button type="button" class="button acsp-remove-url">Remove</button>' +
            '</div>';
        $(this).prev('.acsp-custom-urls').append(html);
    });

    $(document).on('click', '.acsp-remove-url', function () {
        $(this).parent().remove();
    });

    /* -------------------------------------------------------------
     *  2. Settings tab – Hash Allow-List
     * ----------------------------------------------------------- */
    $(function () {
        const box = $('#acsp-hash-list');
        $('#acsp-add-hash').on('click', () => {
            box.append(
                `<div class="acsp-hash-item">
                     <input type="text" name="acsp_hash_values[]" value="" placeholder="sha256-…" class="regular-text code"/>
                     <button type="button" class="button button-small acsp-remove-hash">Remove</button>
                 </div>`
            );
        });
        box.on('click', '.acsp-remove-hash', function () {
            $(this).closest('.acsp-hash-item').remove();
        });
        $('#acsp_enable_hashes').on('change', () =>
            $('.acsp-hash-row').toggle($('#acsp_enable_hashes').prop('checked'))
        );
    });
})(jQuery);
$(document).ready(function() {
    // Function to load subcategories based on selected category
    function loadSubCategories(categoryId, selectedSubCategoryId = null) {
        if (!categoryId) return;

        $.ajax({
            url: '/admin/get-sub-kategori/' + categoryId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                var subCategorySelect = $('#kode_sub_kategori');
                subCategorySelect.empty();
                subCategorySelect.append('<option value="">Pilih Sub Kategori</option>');

                if (data.length > 0) {
                    $.each(data, function(index, item) {
                        var selected = (selectedSubCategoryId && item.id == selectedSubCategoryId) ? 'selected' : '';
                        subCategorySelect.append('<option value="' + item.id + '" ' + selected + '>' + item.nama_sub_kategori + '</option>');
                    });
                    subCategorySelect.prop('disabled', false);
                } else {
                    subCategorySelect.prop('disabled', true);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading subcategories:', error);
                alert('Gagal memuat data sub kategori. Silakan coba lagi.');
            }
        });
    }

    // When category changes, load subcategories
    $('#kode_kategori').change(function() {
        loadSubCategories($(this).val());
    });

    // Load subcategories on page load if a category is selected
    var initialCategoryId = $('#kode_kategori').val();
    var initialSubCategoryId = $('#kode_sub_kategori').data('selected');

    if (initialCategoryId) {
        loadSubCategories(initialCategoryId, initialSubCategoryId);
    }
});

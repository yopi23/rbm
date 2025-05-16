<div class="container">
    <form method="POST" action="{{ route('accessory-restocks.store') }}">
        @csrf

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="supplier_id">Supplier</label>
                    <select name="supplier_id" id="supplier_id" class="form-control" required>
                        <option value="">Pilih Supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h4>Daftar Barang</h4>
            </div>
            <div class="card-body">
                <table class="table" id="items-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Quantity</th>
                            <th>Harga Beli</th>
                            <th>Subtotal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Baris akan ditambahkan via JavaScript -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right"><strong>Total</strong></td>
                            <td id="total-amount">0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

                <button type="button" class="btn btn-sm btn-primary" id="add-item">
                    <i class="fas fa-plus"></i> Tambah Barang
                </button>
            </div>
        </div>

        <div class="form-group">
            <label for="notes">Catatan</label>
            <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Restock</button>
    </form>
</div>

<!-- Template untuk baris item -->
<template id="item-template">
    <tr class="item-row">
        <td>
            <select name="items[][accessory_stock_id]" class="form-control stock-select" required>
                <option value="">Pilih Produk</option>
                @foreach ($stocks as $stock)
                    <option value="{{ $stock->id }}" data-price="{{ $stock->buy_price }}">
                        {{ $stock->name }} ({{ $stock->code }})
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" name="items[][quantity]" class="form-control quantity" min="1" required>
        </td>
        <td>
            <input type="number" name="items[][buy_price]" class="form-control buy-price" min="0" step="100"
                required>
        </td>
        <td class="subtotal">0</td>
        <td>
            <button type="button" class="btn btn-sm btn-danger remove-item">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tambah baris item
        document.getElementById('add-item').addEventListener('click', function() {
            const template = document.getElementById('item-template');
            const tbody = document.querySelector('#items-table tbody');
            const index = tbody.children.length;
            const newRow = document.importNode(template.content, true);

            // Update nama atribut dengan index
            newRow.querySelectorAll('[name]').forEach(el => {
                const name = el.getAttribute('name').replace('[]', `[${index}]`);
                el.setAttribute('name', name);
            });

            tbody.appendChild(newRow);
        });

        // Hapus baris item
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-item')) {
                e.target.closest('tr').remove();
                calculateTotal();
            }
        });

        // Hitung subtotal dan total
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('quantity') ||
                e.target.classList.contains('buy-price')) {
                const row = e.target.closest('tr');
                const quantity = row.querySelector('.quantity').value;
                const price = row.querySelector('.buy-price').value;
                const subtotal = quantity * price;
                row.querySelector('.subtotal').textContent = subtotal.toLocaleString();
                calculateTotal();
            }
        });

        // Auto-fill harga beli saat memilih produk
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('stock-select')) {
                const selectedOption = e.target.options[e.target.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                const row = e.target.closest('tr');
                row.querySelector('.buy-price').value = price;

                // Trigger event input untuk hitung ulang
                const event = new Event('input');
                row.querySelector('.buy-price').dispatchEvent(event);
            }
        });

        // Fungsi hitung total
        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const subtotal = parseFloat(row.querySelector('.subtotal').textContent.replace(/,/g,
                    '')) || 0;
                total += subtotal;
            });
            document.getElementById('total-amount').textContent = total.toLocaleString();
        }

        // Tambah baris pertama saat load
        document.getElementById('add-item').click();
    });
</script>

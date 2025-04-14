<div class="tab-pane establishments_tab fade show active" id="establishments_tab" role="tabpanel">
    <div class="">
        @viteReactRefresh
        @vite('resources/components/App.jsx')

        <div class="d-flex flex-row-fluid gap-5">
            <ul
                class="nav nav-tabs nav-pills rounded shadow-sm p-5 flex-row flex-md-column me-5 mb-3 mb-md-0 fs-6 min-h-450px">
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3 active" data-bs-toggle="tab" href="#establishments-tab">
                        @lang('menuItemLang.establishments')
                    </a>
                </li>
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3" data-bs-toggle="tab" href="#areas-tab">
                        @lang('menuItemLang.areas')
                    </a>
                </li>
                <li class="nav-item w-md-200px me-0 py-1 nav-link-taxes">
                    <a class="nav-link py-3" data-bs-toggle="tab" href="#tables_tab">
                        @lang('menuItemLang.tables')
                    </a>
                </li>
                <li class="nav-item w-md-200px me-0 py-1 nav-link-methods">
                    <a class="nav-link py-3" data-bs-toggle="tab" href="#tables_qr_tab">
                        @lang('menuItemLang.tables_qr')
                    </a>
                </li>
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3" data-bs-toggle="tab" href="#menu_qr-tab">
                        @lang('menuItemLang.menu_qr')
                    </a>
                </li>
            </ul>

            <div class="tab-content w-100">
                <div class="tab-pane establishments_tab-pane fade show active" id="establishments-tab" role="tabpanel">
                    <div class="root" data-type="establishment"
                        dir="{{ app()->getLocale() == 'en' ? 'ltr' : 'rtl' }}">
                    </div>
                </div>

                <div class="tab-pane establishments_tab-pane fade" id="areas-tab" role="tabpanel">
                    <div class="root" type="area"
                        list-url="{{ json_encode(route('areaMiniList')) }}"
                        area-url="{{ json_encode(route('area.store')) }}"
                        dir="{{ app()->getLocale() == 'en' ? 'ltr' : 'rtl' }}">
                    </div>
                </div>

                <div class="tab-pane establishments_tab-pane fade" id="tables_tab" role="tabpanel">
                    <div class="root" type="table"
                        list-url="{{ json_encode(route('tableList')) }}"
                        table-url="{{ json_encode(route('table.store')) }}"
                        listTableStatus-url="{{ json_encode(route('table-status-type-values')) }}"
                        dir="{{ app()->getLocale() == 'en' ? 'ltr' : 'rtl' }}">
                    </div>
                </div>

                <div class="tab-pane fade" id="tables_qr_tab" role="tabpanel">
                    <div class="root" data-type="tableQR"
                        data-list-url="{{ route('tableList') }}"
                        dir="{{ app()->getLocale() == 'en' ? 'ltr' : 'rtl' }}">
                    </div>
                </div>

                <div class="tab-pane fade" id="menu_qr-tab" role="tabpanel">
                    <div class="root"
                        type="menuQR"
                        list-url="{{ json_encode(route('order.products')) }}"
                        logo-url="/assets/media/logos/1-01.png"
                        dir="{{ app()->getLocale() == 'en' ? 'ltr' : 'rtl' }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const rootClass = '.root';

        function updateRootId() {
            // شيل id="root" من كل العناصر
            document.querySelectorAll(`.tab-content .establishments_tab-pane ${rootClass}[id="root"]`).forEach(el => {
                el.removeAttribute('id');
            });


            // ضيف id="root" للتاب النشطة
            const activeTab = document.querySelector(`.tab-content .establishments_tab-pane.active ${rootClass}`);
            console.log(activeTab);

            if (activeTab) {
                activeTab.setAttribute('id', 'root');
            }
        }

        // أول مرة عند التحميل
        updateRootId();

        // عند تغيير التاب
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function () {
                // أعطي فرصة للـ DOM يتحدث
                setTimeout(() => {
                    updateRootId();
                }, 10);
            });
        });
    });
</script>


/*
 * Plugin Alpine ceplok-ui/design-kit-livewire: mendaftarkan perilaku
 * interaktif komponen kit. Pasang sekali di resources/js/app.js.
 *
 * Dengan Livewire 4 (Alpine sudah dibawa Livewire; layout memakai
 * @livewireScriptConfig, bukan @livewireScripts):
 *
 *   import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
 *   import ceplok from '../../vendor/ceplok-ui/design-kit-livewire/resources/js/index.js';
 *
 *   Alpine.plugin(ceplok);
 *   Livewire.start();
 *
 * Tanpa Livewire:
 *
 *   import Alpine from 'alpinejs';
 *   import ceplok from '../../vendor/ceplok-ui/design-kit-livewire/resources/js/index.js';
 *
 *   Alpine.plugin(ceplok);
 *   Alpine.start();
 */
import avatar from './components/avatar.js';
import datepicker from './components/datepicker.js';
import dismissible from './components/dismissible.js';
import dropdown from './components/dropdown.js';
import floatingLabel from './components/floating-label.js';
import inputField from './components/input-field.js';
import modal from './components/modal.js';
import pagination from './components/pagination.js';
import popover from './components/popover.js';
import search from './components/search.js';
import select from './components/select.js';
import table from './components/table.js';
import tablePreview from './components/table-preview.js';
import textArea from './components/text-area.js';
import upload from './components/upload.js';

export default function ceplok(Alpine) {
    Alpine.data('ceplokAvatar', avatar);
    Alpine.data('ceplokDatepicker', datepicker);
    Alpine.data('ceplokDismissible', dismissible);
    Alpine.data('ceplokDropdown', dropdown);
    Alpine.data('ceplokFloatingLabel', floatingLabel);
    Alpine.data('ceplokInputField', inputField);
    Alpine.data('ceplokModal', modal);
    Alpine.data('ceplokPagination', pagination);
    Alpine.data('ceplokPopover', popover);
    Alpine.data('ceplokSearch', search);
    Alpine.data('ceplokSelect', select);
    Alpine.data('ceplokTable', table);
    Alpine.data('ceplokTablePreview', tablePreview);
    Alpine.data('ceplokTextArea', textArea);
    Alpine.data('ceplokUpload', upload);
}

export { avatar, datepicker, dismissible, dropdown, floatingLabel, inputField, modal, pagination, popover, search, select, table, tablePreview, textArea, upload };

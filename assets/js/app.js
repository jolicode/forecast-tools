require('../css/main.scss');
const $ = require('jquery');

import 'bootstrap/js/dist/alert';
import 'bootstrap/js/dist/collapse';
import 'bootstrap/js/dist/dropdown';
import 'bootstrap/js/dist/modal';
import 'bootstrap/js/dist/tab';
import './select2';
import dynamicForm from './dynamicForm';

global.$ = global.jQuery = $;
global.dynamicForm = dynamicForm;

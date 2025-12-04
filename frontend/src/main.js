import './styles/index.css';
import 'flowbite';
import 'preline';
import { HomePage } from './pages/HomePage';

if (window.HSStaticMethods) {
  window.HSStaticMethods.autoInit();
}

const root = document.getElementById('app');
root.innerHTML = HomePage();

import { toast } from "react-toastify";

export const showToastWithPromise = (message, result, options = {}) => {
    return new Promise((resolve) => {
      toast[result](message, {
        ...options,
        onClose: () => {
          if (options.onClose) options.onClose(); // Если есть существующий обработчик onClose, вызовем его
          resolve(); // Разрешаем Promise при закрытии тоста
        }
      });
    });
  }
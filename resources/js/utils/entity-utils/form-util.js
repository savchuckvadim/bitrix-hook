export const appendFormData = (formData, key, value) => {
    if (value instanceof File) {
        // Если значение - файл, добавляем его
        formData.append(key, value);
    } else if (value instanceof FileList) {
        // Если значение - FileList, обрабатываем каждый файл
        Array.from(value).forEach((file, index) => {
            formData.append(`${key}_${index}`, file);
        });
    } else if (Array.isArray(value)) {
        // Если значение - массив, обрабатываем каждый его элемент
        value.forEach((item, index) => {
            for (const subKey in item) {
                if (item[subKey] instanceof FileList) {
                    // Обработка FileList
                    Array.from(item[subKey]).forEach((file, fileIndex) => {
                        formData.append(`${key}_${index}_${subKey}_${fileIndex}`, file);
                    });
                } else {
                    // Для других типов данных
                    appendFormData(formData, `${key}_${index}_${subKey}`, item[subKey]);
                }
            }
        });
    } else if (typeof value === 'object' && value !== null) {
        // Если значение - объект (не массив), рекурсивно обрабатываем каждое его поле
        for (const subKey in value) {
            if (value[subKey] instanceof FileList) {
                // Обработка FileList
                Array.from(value[subKey]).forEach((file, fileIndex) => {
                    formData.append(`${key}_${subKey}_${fileIndex}`, file);
                });
            } else {
                // Для других типов данных
                appendFormData(formData, `${key}_${subKey}`, value[subKey]);
            }
        }
    } else {
        // Для всех остальных типов данных просто добавляем их в formData
        formData.append(key, value);
    }
    
    return formData;
};
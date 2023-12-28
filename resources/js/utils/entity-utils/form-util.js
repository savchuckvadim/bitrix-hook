export const appendFormData = (formData, key, value) => {
    if (value instanceof File) {
        // Если значение - файл, добавляем его
        formData.append(key, value);
    } else if (Array.isArray(value)) {
        // Если значение - массив, обрабатываем каждый его элемент
        value.forEach((item, index) => {
            // Для каждого элемента массива обрабатываем его как объект
            for (const subKey in item) {
                appendFormData(formData, `${key}[${index}].${subKey}`, item[subKey]);
            }
        });
    } else if (typeof value === 'object' && value !== null) {
        // Если значение - объект (не массив), рекурсивно обрабатываем каждое его поле
        for (const subKey in value) {
            appendFormData(formData, `${key}.${subKey}`, value[subKey]);
        }
    } else {
        // Для всех остальных типов данных просто добавляем их в formData
        formData.append(key, value);
    }
    return formData
}
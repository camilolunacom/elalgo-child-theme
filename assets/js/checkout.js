document.addEventListener('DOMContentLoaded', () => {
  const holidays = [
    '2022-01-01',
    '2022-01-10',
    '2022-03-21',
    '2022-04-14',
    '2022-04-15',
    '2022-05-01',
    '2022-05-30',
    '2022-06-20',
    '2022-06-27',
    '2022-07-04',
    '2022-07-20',
    '2022-08-07',
    '2022-08-15',
    '2022-10-17',
    '2022-11-07',
    '2022-11-14',
    '2022-12-08',
    '2022-12-25',
  ];

  const input = document.querySelector('#billing_delivery');

  const exceptions = [];

  const delivery = new Date();

  const deliveryToday = window.canDeliverToday;
  const dayOfWeek = delivery.getDay();
  const afternoon = delivery.getHours() > 13;

  /**
   * Format date
   * @param {date} date Date object to be formatted
   * @returns Date string formatted
   */
  function formatDate(date) {
    return date.toISOString().split('T')[0];
  }

  /**
   * Modify the date to next day
   * @param {date} date Date object
   */
  function nextDay(date) {
    date.setDate(date.getDate() + 1);
  }

  function isHoliday(date) {
    const dateString = formatDate(date);
    if (!exceptions.includes(dateString)) {
      if (date.getDay() === 0 || holidays.includes(dateString)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Check if date is a Sunday or holiday and if so, skip to the next day
   * @param {date} date Date object
   */
  function notHoliday(date) {
    if (isHoliday(date)) {
      nextDay(date);
      notHoliday(date);
    }
  }

  /**
   * Check what the earliest delivery date could be
   * @param {date} date Date object
   * @returns New date when delivery can happen
   */
  function minDeliveryDate(date) {
    if (dayOfWeek === 6 || dayOfWeek === 0) {
      date.setHours(0, 0, 0, 0);
      nextDay(date);
    } else if (dayOfWeek === 5 && afternoon && !deliveryToday) {
      date.setHours(0, 0, 0, 0);
      nextDay(date);
      nextDay(date);
    } else if (afternoon || !deliveryToday) {
      nextDay(date);
    }
    notHoliday(date);
    return date;
  }

  function getValidationP() {
    let validation = document.querySelector('.alzr-validation');
    if (!validation) {
      validation = document.createElement('p');
      validation.classList.add('form-row', 'form-row-wide', 'alzr-validation');
      document
        .querySelector('.woocommerce-billing-fields__field-wrapper')
        .appendChild(validation);
    }
    return validation;
  }

  const nextAvailableDelivery = minDeliveryDate(delivery);

  /**
   * Handler for input field
   * @param {event} e Input event
   */
  function validateDeliveryDate(e) {
    const field = e.target;
    const date = new Date(`${field.value}T00:00`);
    const validation = getValidationP();
    if (isHoliday(date)) {
      validation.innerHTML =
        'Lo sentimos, no hacemos entregas domingos ni festivos. Por favor escoge otra fecha.';
      field.classList.add('alzr-invalid');
    } else if (date < nextAvailableDelivery) {
      validation.innerHTML =
        'Lo sentimos, la fecha escogida es en el pasado. Por favor escoge otra fecha.';
      field.classList.add('alzr-invalid');
    } else {
      validation.innerHTML = '';
      field.classList.remove('alzr-invalid');
    }
  }

  const nextAvailableDeliveryString = formatDate(nextAvailableDelivery);

  input.setAttribute('min', nextAvailableDeliveryString);
  input.setAttribute('pattern', 'd{4}-d{2}-d{2}');
  input.addEventListener('input', validateDeliveryDate);

  input.value = nextAvailableDeliveryString;
});

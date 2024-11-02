const currDate = new Date();
export const defaultMenuTime = [{
    from_date: `${currDate.getFullYear()}-${(currDate.getMonth()+1).toString().padStart(2, '0')}-${currDate.getDate().toString().padStart(2, '0')}`,
    to_date: null,
    active: 1,
    times: [
        { day_no: '1', from_time: '00:00:00', to_time: '23:59:59', active: 0 },
        { day_no: '2', from_time: '00:00:00', to_time: '23:59:59', active: 0 },
        { day_no: '3', from_time: '00:00:00', to_time: '23:59:59', active: 0 },
        { day_no: '4', from_time: '00:00:00', to_time: '23:59:59', active: 0 },
        { day_no: '5', from_time: '00:00:00', to_time: '23:59:59', active: 0 },
        { day_no: '6', from_time: '00:00:00', to_time: '23:59:59', active: 0 },
        { day_no: '7', from_time: '00:00:00', to_time: '23:59:59', active: 0 }
    ]
}];
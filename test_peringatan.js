fetch('http://localhost:8000/api/peringatan-dini?is_active=1')
  .then(res => res.json())
  .then(data => {
    console.log("Returned data length:", data.data.length);
    const hasInactive = data.data.some(d => !d.is_active || d.is_active === 0 || d.is_active === '0');
    console.log("Has inactive:", hasInactive);
    data.data.forEach(d => console.log(d.id, 'is_active:', d.is_active));
  })
  .catch(err => console.error(err));

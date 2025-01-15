import React, { useState } from 'react';
import TreeTableComponentLocal from "../comp/TreeTableComponentLocal";
import SweetAlert2 from 'react-sweetalert2';
import { getRowName } from '../lang/Utils';

const DataImport = ({ translations, dir }) => {
  const rootElement = document.getElementById('root');
  const templateUrl = rootElement.getAttribute('template-url');
  const type = rootElement.getAttribute('type');
  const dataType = rootElement.getAttribute('data-type');
  const [data, setData] = useState([]);
  const [cols, setCols] = useState([]);
  const [file, setFile] = useState(null);
  const [showAlert, setShowAlert] = useState(false);
  // Handle file input change
  const handleFileChange = (e) => {
    const formData = new FormData();
    formData.append('file', e.target.files[0]);
    axios.post(`/${type}/readData`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    }).then((response) => {
      setCols(Object.keys(response.data[0]).map((key)=>{
        return { key: key, autoFocus: false, type: "Text", width: '15%', editable: false }
      }));
      setData(response.data.slice(1));
    });
    const selectedFile = e.target.files[0];
    setFile(selectedFile);
  };

  const handleFileUpload = async () => {
    if (!file) {
      alert('Please select a file first!');
      return;
    }

    const formData = new FormData();
    formData.append('file', file);

    try {
      const response = await axios.post(`/${type}/upload`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      if (response.data.message == 'Done') {
        Swal.fire({
          show: showAlert,
          title: '',
          html: `${translations[dataType]} - ${translations.importedsuccessfully}`,
          icon: "info",
          timer: 4000,
          showCancelButton: false,
          showConfirmButton: false,
        }).then(() => {
          setShowAlert(false); // Reset the state after alert is dismissed
        });
      }
      else {
        Swal.fire({
          show: showAlert,
          title: 'Error',
          html: `<div>${translations[dataType]} - ${translations.errorInImport}</div>${getErrorMessage(response.data.errors)}`,
          icon: "error",
          timer: 4000,
          showCancelButton: false,
          showConfirmButton: false,
        }).then(() => {
          setShowAlert(false); // Reset the state after alert is dismissed
        });
      }

    } catch (error) {
      console.log(error);
      alert('Error uploading file');
    }
  }

  const getErrorMessage = (data) => {
    let res = ''
    for (let index = 0; index < data.length; index++) {
      const element = data[index];
      res += `<div>${translations.product}: ${getRowName(element.row)}</div>`;
      res += `<div>${element.message.message.split('_').map(m => `${translations[m]} `)}: ${element.message.data.map(m => `${!!translations[m] ? translations[m] : m}, `)}</div>`;
    }
    return res;
  }

  const handleImportError = (data) => {
    setShowAlert(true);
    Swal.fire({
      show: showAlert,
      title: 'Error',
      html: `<div>${translations.exist}</div>${getErrorMessage(data)}`,
      icon: "error",
      timer: 4000,
      showCancelButton: false,
      showConfirmButton: false,
    }).then(() => {
      setShowAlert(false); // Reset the state after alert is dismissed
    });
  }


  return (
    <div class="card mb-5 mb-xl-8">
      <SweetAlert2 />

      <div class="card-header border-0 pt-5">
        <h3 class="card-title align-items-start flex-column">
          <span class="card-label fw-bold fs-3 mb-1">{translations[type]}</span>
          <span class="text-muted mt-1 fw-semibold fs-7">{translations[type]}</span>
        </h3>
        <div class="card-toolbar">
          <div class="d-flex align-items-center gap-2 gap-lg-3">
            <a href={templateUrl} style={{"width" : "10rem"}}>{translations.downloadTemplate}</a>
            <input id="uploadProduct" className="form-control" type="file" accept=".xlsx, .xls, .csv"
              onChange={handleFileChange} />
            <button class="btn btn-primary" onClick={handleFileUpload}>{translations.import1}</button>
          </div>
        </div>
      </div>
      <div class="card-body">
          <TreeTableComponentLocal
            translations={translations}
            dir={dir}
            header={false}
            addNewRow={false}
            type={dataType}
            title={translations[dataType]}
            currentNodes={data}
            cols={cols}
            actions={[]}
            onUpdate={null}
            onDelete={null}
          />
      </div>

    </div>
  );
};


export default DataImport;
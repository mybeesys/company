import axios from 'axios';
import { useState } from 'react';
import SweetAlert2 from 'react-sweetalert2';
import DropdownMenu from '../../comp/DropdownMenu';
import TreeTableComponent from '../../comp/TreeTableComponent';

const PrepTable = ({ dir, translations }) => {
  const rootElement = document.getElementById('root');
  const urlList = JSON.parse(rootElement.getAttribute('list-url'));
  const [showAlert, setShowAlert] = useState(false);
  const [currentRow, setCurrentRow] = useState({});

  const changeStatus = (data, status, afterExecute)=>{    
    axios.post('statusUpdate', {id: data.id, status: status})
        .then((resp)=>{
          data["status"] = resp.data.status;
          Swal.fire({
            show: showAlert,
            title: `${data.ref_no} ${translations[data["status"]]}`,
            icon: "success",
            timer: 2000,
            showCancelButton: false,
            showConfirmButton: false,
           }).then(() => {
            setShowAlert(false); // Reset the state after alert is dismissed
          });
          afterExecute(data);
        })
    .catch((ex)=>{});
  }

  const statusCell = (data, key, editMode, editable) => {
    return !!editMode? <></>: <span class={`status status-${data[key]}`}>{` ${translations[data[key]]}`}</span>
  }

  const dropdownCell = (data, key, editMode, editable, refreshTree) => {
    let actions = [];
    if(data.status != "approved")
      actions.push({key:"approved", action: (data, afterExecute)=>{
        changeStatus(data, 'approved', afterExecute);
      }});
    return <DropdownMenu actions={actions} data={data} translations={translations} afterExecute={refreshTree}/>;
  }

  const canEditRow=(data)=>{
    return data.status == 'draft';
  }

  const prepareData = (data) =>{
    return data.map((row)=>{
      return {key: row.id, data: {...row}};
    });
  }

  return (
    <div>
      <SweetAlert2 />
      <TreeTableComponent
        translations={translations}
        dir={dir}
        urlList={urlList}
        editUrl={'prep/%/edit'}
        addUrl={'prep/create'}
        canEditRow={canEditRow}
        canAddInline={false}
        title="preps"
        cols={[
          {key : "ref_no", title:"number", autoFocus: true, type :"Text", width:'15%'},
          {key : "establishment", autoFocus: true, type :"AsyncDropDown", width:'15%'},
          {key : "product", autoFocus: true, type :"AsyncDropDown", width:'15%'},
          {key : "total_before_tax", title: "total", autoFocus: true, type :"Decimal", width:'15%'},
          {key : "transaction_date", title: "date", autoFocus: true, type :"Date", width:'15%'},
          {key : "status",title: "op_status", autoFocus: true, type :"Date", width:'15%',
              customCell : statusCell
          },
          {key : "dd", autoFocus: true, type :"Date", width:'15%',
            customCell : dropdownCell
          }
        ]}
        prepareData={prepareData}
      />
    </div>
  );
};

export default PrepTable;
import TreeTableComponent from '../comp/TreeTableComponent';


const AreaTable = ({ dir, translations }) => {
  const rootElement = document.getElementById('root');
  const urlList = JSON.parse(rootElement.getAttribute('list-url'));

  const validateObject = (data) => {
    if (!!!data.establishment) return `${translations.establishment} ${translations.required}`;
    return 'Success';
  }

  return (
    <div>
      <TreeTableComponent
        translations={translations}
        dir={dir}
        urlList={urlList}
        canAddInline={true}
        title="areas"
        defaultValue={{ active: 1 }}
        validateObject={validateObject}
        cols={[
          { key: "establishment", autoFocus: false, type: "AsyncDropDown", searchUrl: 'searchEstablishments', width: '30%', editable: true },
          { key: "name_en", autoFocus: true, type: "Text", width: '16%', editable: true , required : true},
          { key: "name_ar", autoFocus: false, type: "Text", width: '15%', editable: true , required : true},
          { key: "active", autoFocus: false, options: [], type: "Check", width: '30%', editable: true }
        ]}
      />
    </div>
  );
};

export default AreaTable;
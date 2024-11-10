import TreeTableComponent from '../comp/TreeTableComponent';


const LinkedComboTable = ({dir, translations}) => {
  const rootElement = document.getElementById('root');
  const urlList = JSON.parse(rootElement.getAttribute('list-url'));

  return (
    <div>
      <TreeTableComponent 
        translations={translations}
        dir={dir}
        urlList={urlList}
        editUrl={'linkedCombo/%/edit'}
        addUrl={'linkedCombo/create'}
        canAddInline={false}
        title="linkedCombos"
        cols={[
            {key : "name_en", autoFocus: true, options: [], type :"Text", width:'16%'},
            {key : "name_ar", autoFocus: false, options: [], type :"Text", width:'15%'},
            {key : "active",  autoFocus: false, options: [], type :"Check", width:'30%'}
        ]}
      />
    </div>
  );
};

export default LinkedComboTable;
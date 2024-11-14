import TreeTableComponent from '../comp/TreeTableComponent';

const UnitTree = ({translations, dir}) => 
    {
        const rootElement = document.getElementById('root');
        const urlList = JSON.parse(rootElement.getAttribute('list-url'));

        return (  
        <TreeTableComponent 
        translations={translations}
        dir={dir}
        urlList={urlList}
        editUrl={null}
        addUrl={null}
        canAddInline={true}
        title="Unit"
        cols={[
            {key : "name_en", autoFocus: true, options: [], type :"Text", width:'16%'},
            {key : "name_ar", autoFocus: false, options: [], type :"Text", width:'15%'}
        ]}
      /> );
       
    };        
export default UnitTree;
 
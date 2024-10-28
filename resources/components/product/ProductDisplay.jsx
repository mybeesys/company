import React , { useState, useCallback  } from 'react';
import ReactDOM from 'react-dom/client';
import { useDropzone } from 'react-dropzone';
import axios from 'axios';
import { SketchPicker, BlockPicker } from "react-color";

const ProductDisplay = ({translations ,parentHandlechanges ,product ,saveChanges}) => {
    const rootElement = document.getElementById('root');
    let  imageurl = rootElement.getAttribute('image-url');
    let dir = rootElement.getAttribute('dir');
    const [currentObject, setcurrentObject] = useState(product); 
    const [files, setFiles] = useState([]);
    const [imageSrc, setimageSrc] = useState(imageurl);
      //creating state to store our color and also set color using onChange event for sketch picker
    const [sketchPickerColor, setSketchPickerColor] = useState({
    r: "241",
    g: "112",
    b: "19",
    a: "1",
  });
  // destructuring rgba from state
   const { r, g, b, a } = sketchPickerColor;

  //creating state to store our color and also set color using onChange event for block picker
   const [blockPickerColor, setBlockPickerColor] = useState(currentObject.color?currentObject.color : "#37d67a");



    const onDrop = useCallback((acceptedFiles) => {
        const mappedFiles = acceptedFiles.map((file) => 
          Object.assign(file, {
            preview: URL.createObjectURL(file)
          })
        );
        setFiles((prevFiles) => [...prevFiles, ...mappedFiles]);
        handleChange('image_file' , mappedFiles[0], currentObject);
        setimageSrc(mappedFiles[0].preview);
      }, [currentObject]);
    
      const { getRootProps, getInputProps } = useDropzone({
        onDrop,
        accept: 'image/*',
        multiple: false
      });

      const deleteImage =() =>
      {
        setFiles([]);
        setimageSrc(imageurl);
        handleChange('image_file' , null);
      }
 
   const handleChange = async (key , value) =>
    {
        let r = {...currentObject};
        r[key] = value;
        setcurrentObject({...r});
        parentHandlechanges({...r});
        console.log(r);
    }

    const clickSubmit =(event) =>
    {
        event.preventDefault();
            event.stopPropagation();
            const form = event.currentTarget;
            if (form.checkValidity() === false) {
                setValidated(true);
        form.classList.add('was-validated');
                return;
            }
        else
        {
        saveChanges();
        }

    }
  // Clean up object URLs to avoid memory leaks
  React.useEffect(() => {

  }, []);
      
    return (
      <>
            <div class="card-body" dir={dir}>
              <form onSubmit={(event)=>clickSubmit(event)}>
                 <div class="form-group" style={{paddingtop: '5px'}}>
                 <div class="row">
                 <div class="col-6">
                    <div class="image-input image-input-empty image-input-outline mb-3 mx-auto text-center" data-kt-image-input="true"
                    style={{maxWidth: '180px' , position: 'relative'}}>
                    <div {...getRootProps({ className: 'image-input-wrapper w-150px h-150px mx-auto' })}>
                        <input {...getInputProps()}  />
                        <img src= {imageSrc} style={{maxWidth: '180px' , position: 'relative'}} />
                    </div>
                    <label className="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" onClick={deleteImage}
                        style={{position: 'absolute' , top: '10px' , right: '10px'}} data-kt-image-input-action="change" data-bs-toggle="tooltip">
                        <i class="ki-outline ki-cross fs-7"></i>
                    </label>
                    </div>
                </div>
                <div class="col-6">
                    <div>
                    <div className="blockpicker">
                      <div
                        style={{
                          backgroundColor: `${blockPickerColor}`,
                          width: 100,
                          height: 50,
                          border: "2px solid white",
                        }}
                      ></div>
                      {/* Block Picker from react-color and handling color on onChange event */}
                      <BlockPicker
                        color={blockPickerColor}
                        onChange={(color) => {
                          handleChange('color', color.hex)
                          setBlockPickerColor(color.hex);
                        }}
                      />
                    </div>
                    </div>
                    </div>
              </div>
            </div>
            <input type="submit" id="btnSubmit" hidden></input>
            </form>
        </div>      
     </>

    );
  };

    
  export default ProductDisplay;
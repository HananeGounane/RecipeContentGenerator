(function (wp, $) {

    $('#cog_generate_all_button').on('click', function () {
        const topic = $('#cog_topic').val();
        const paa = $('#cog_paa').val();
        const blockType = $('#cog_block_type').val();
        if (!topic) {
            alert("Please enter a topic.");
            return;
        }
        $('#cog_output').html("Generating article...");
        $.ajax({
            url: cog_params.ajax_url,
            method: 'POST',
            data: {
                action: 'generate_all_article',
                topic: topic,
                paa: paa
            },
            success: function (response) {
                if (response.success) {
                    $('#cog_output').html("Article generated successfully! Processing content...");
                    let article = response.data.article.replace('```html', '').replace('```', '');
                    addArticleContent(article, response.data.metadesc)
                    $('#cog_output').html("Content processed successfully! Processing SEO informations...");

                    updateRankMathMeta(response.data.seoTitle.trim(), response.data.metadesc.trim(), response.data.keywords.trim(), response.data.slug.trim());
                    $('#cog_output').html("SEO Informations added successfully!");
                 
                    wp.data.dispatch('core/editor').savePost();
                } else {
                    $('#cog_output').html("Error: " + response.data.message);
                }
            },
            error: function () {
                $('#cog_output').html("Error generating outline.");
            }
        });
    });
    window.previewFile = function(event, index) {
        const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            // Check if preview image already exists
            let previewImg = $(`#preview-image-${index}`);
            previewImg.attr("src", e.target.result).show();
            $(`#preview-container-${index}`).css({
                "display": "block"
            });
        };
        reader.readAsDataURL(file);
    }
    }
    window.uploadImage = function(index) {
        const fileInput = document.getElementById(`file-input-${index}`);
        const file = fileInput.files[0];
        const altText = $('#image-alt-'+index).text()
        const title = $('#image-title-'+index).text()
        const caption = $('#image-caption-'+index).text()
        const description = $('#image-description-'+index).text()
        const emplacement = $('#image-emplacement-'+index).text()
        if (!file) {
            alert("Please select an image to upload.");
            return;
        }
        $(`#preview-container-${index} .upload-status`).html('');
        // Show uploading status
        $(`#preview-container-${index} .upload-status`).html(`Uploading...</p>`);
    
        const formData = new FormData();
        formData.append("action", "cog_upload_image_from_file");
        formData.append("file", file);
        formData.append("alt", altText);
        formData.append("title", title);
        formData.append("caption", caption);
        formData.append("description", description);
        formData.append("emplacement", emplacement);
    
        // Send image to WordPress via AJAX
        $.ajax({
            url: cog_params.ajax_url,  // Use cog_params like the original function
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response.success) {
                    if (emplacement.toLowerCase() === 'featured'.toLowerCase()) {
                        // Set the image as the featured image
                        wp.data.dispatch('core/editor').editPost({
                            featured_media: response.data.attachment_id
                        });
                        $('#cog_image_output').html(`Featured Image uploaded!`);
                    } else { 
                        const blocks = wp.data.select('core/block-editor').getBlocks();
                        let targetBlock = null;
                    
                        // Find the block you want (e.g., the block with a specific title or ID)
                        blocks.forEach(block => {
                            if (block?.attributes?.content?.text === emplacement) {
                                targetBlock = block;
                            }
                        });
                        if (targetBlock) {
                            const imageBlock = wp.blocks.createBlock('core/image', {
                                id: response.data.attachment_id,
                                url: response.data.attachment_url,
                                alt: altText,
                                caption: caption
                            });
                    
                            // Insert the new block after the target block
                            const selectedBlockIndex = wp.data.select('core/block-editor').getBlockIndex(targetBlock.clientId);                    
                            if (selectedBlockIndex !== -1) {
                                wp.data.dispatch('core/block-editor').insertBlock(imageBlock, selectedBlockIndex + 1);
                            } else {
                                wp.data.dispatch('core/block-editor').insertBlock(imageBlock);
                            }
                        }       
                    }
                    // Update UI
                    $(`#preview-container-${index} .upload-status`).html(`✅ Image uploaded!`);
                    wp.data.dispatch('core/editor').savePost();

                } else {
                    $(`#preview-container-${index} .upload-status`).html(`❌ Upload failed: ${response.data.message}`);
                }
            },
            error: function () {
                $(`#preview-container-${index} .upload-status`).html("❌ Upload error.");
            }
        });

        
    }
    $('#cog_add_recipe_button').on('click', function () {   
        if(window.WPRecipeMaker) {
            const article = wp.data.select('core/editor').getEditedPostContent();
            generateRecipe(article)
        }
    }) 
    function addArticleContent(article, meta) {
        wp.data.dispatch('core/block-editor').resetBlocks([]);
        const parser = new DOMParser();
        const doc = parser.parseFromString(cleanContent(article) , 'text/html');
        let blocks = []
        doc.body.childNodes.forEach(node => {
            if (node.nodeType === Node.ELEMENT_NODE) {
                const result = processNode(node, meta)
                if(result)
                    blocks.push(result);
            }
        });                  
        wp.data.dispatch('core/block-editor').insertBlocks(blocks);
        wp.data.dispatch('core/editor').savePost();
    }
    function generateRecipe(article) {
        const topic = wp.data.select('rank-math').getKeywords().split(',')[0];
        if (!article) {
            alert("Please generate the article.");
            return;
        }
        if (!topic) {
            alert("Please enter a topic.");
            return;
        }
        $('#cog_output').html("Generating recipe...");
        $.ajax({
            url: cog_params.ajax_url,
            method: 'POST',
            data: {
                action: 'generate_recipe',
                topic: topic,
                article: article
            },
            success: function (response) {
                if (response.success) {
                    let recipeIdVar; // Declare a variable outside

                    getRecipeIdByName(topic).then((recipeId) => {
                        recipeIdVar = recipeId; // Store the value in the variable
                        createOrUpdateRecipe(response.data.recipe, recipeIdVar)
                        // location.reload();
                        console.log("Stored Recipe ID:", recipeIdVar);
                    });
                }
            },
            error: function () {
                $('#cog_output').html("Error generating recipe.");
            }
            });
    }
    const   createOrUpdateRecipe = async (recipeData, postId = null) => {
        try {
            const recipe = {recipe: JSON.parse(recipeData.replace('```json', '').replace('```',''))}
            // REST API endpoint
            const endpoint = postId
                ? `/wp-json/wp/v2/wprm_recipe/${postId}` // Update recipe if postId exists
                : `/wp-json/wp/v2/wprm_recipe`; // Create new recipe if no postId
    
            const response = await fetch(endpoint, {
                method: postId ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.wpApiSettings.nonce, // Authentication nonce
                },
                body: JSON.stringify(recipe)
            });
            if (!response.ok) {
                throw new Error(`Failed to ${postId ? 'update' : 'create'} recipe: ${response.statusText}`);
            }
    
            const result = await response.json();
            console.log(`${postId ? 'Updated' : 'Created'} recipe successfully:`, result);
            const blocks = wp.data.select('core/block-editor').getBlocks();

            // Find existing recipe block (search using ID)
            const existingBlock = blocks.find(block => 
                block.name === 'wp-recipe-maker/recipe' && block.attributes.id === result.id
            );

            // Remove the existing block if found
            if (existingBlock) {
                console.log(`Removing existing recipe block with ID: ${result.id}`);
                wp.data.dispatch('core/block-editor').removeBlock(existingBlock.clientId);
            }
    
            // Create a new recipe block
            const recipeBlock = wp.blocks.createBlock('wp-recipe-maker/recipe', { id: result.id });
    
            // Insert the new block at the end of the content
            wp.data.dispatch('core/block-editor').insertBlocks(recipeBlock);
    
            // Save the post
            wp.data.dispatch('core/editor').savePost();
    
            // Display success message
            $('#cog_output').html(`Recipe ${postId ? 'updated' : 'added'} successfully`);
    
            return result;
        } catch (error) {
            console.error('Error creating/updating recipe:', error);
            return null;
        }
    };
    
    $('#cog_generate_more_words').on('click', function () {
        const article = wp.data.select('core/editor').getEditedPostContent();
        $('#cog_output').html("Adding words to the article...");
        $.ajax({
            url: cog_params.ajax_url,
            method: 'POST',
            data: {
                action: 'generate_more_words',
                article: article
            },
            success: function (response) {
                if (response.success) {
                    $('#cog_output').html("New article version generated successfully!");
                    let article = response.data.article.replace('```html', '').replace('```', '');
                    // Dispatch the editPost action to update the title
                    
                    addArticleContent(article, "test")

                } else {
                    $('#cog_output').html("Error: " + response.data.message);
                }
            },
            error: function (error) {
                $('#cog_output').html("Error generating outline.");
                console.log(error)
            }
        });
    });
    const getRecipeIdByName = async (recipeName) => {
        try {
            // Step 1: Get the post content
            const { parse } = wp.blocks;
            const postContent = wp.data.select('core/editor').getEditedPostContent();
            const blocks = parse(postContent);
    
            // Step 2: Look for a WPRM Recipe block
            const recipeBlock = blocks.find(block => block.name === 'wp-recipe-maker/recipe');
    
            if (recipeBlock) {
                console.log("Found recipe block:", recipeBlock);
                return recipeBlock.attributes.id; // Return the recipe ID from the block
            }
    
            // Step 3: If no recipe block found, search by name in API
            console.log("No recipe block found, searching via API...");
            const response = await fetch(`/wp-json/wp/v2/wprm_recipe?search=${encodeURIComponent(recipeName)}`);
    
            if (!response.ok) {
                throw new Error(`Failed to fetch recipes: ${response.statusText}`);
            }
    
            const recipes = await response.json();
    
            if (recipes.length > 0) {
                console.log("Found recipe via API:", recipes[0]);
                return recipes[0].id; // Return the first matching recipe ID
            } else {
                console.log("Recipe not found.");
                return null;
            }
        } catch (error) {
            console.error("Error fetching recipe ID:", error);
            return null;
        }
    };
    
    
    const cleanContent = (text) => {
        return text
        .replace(/\*\*(.*?)\*\*/g, '$1')  // Remove bold (double asterisks)
        .replace(/\*(.*?)\*/g, '$1')      // Remove italics (single asterisks)
        .replace(/__(.*?)__/g, '$1')      // Remove bold (double underscores)
        .replace(/_(?!\w)(.*?)_(?!\w)/g, '$1')  // Remove italics (single underscores), but not if part of a word
        .replace(/\[(.*?)\]\(.*?\)/g, '$1') // Remove markdown links [text](url)
        .trim();
    };
    
    $('#cog_suggest_links_button').on('click', async function () {
        const article = wp.data.select('core/editor').getEditedPostContent();
    
        if (!article) {
            alert("Please generate or enter content first.");
            return;
        }
    
        const includeDrafts = $("#include_drafts").is(":checked") ? 'draft,publish' : 'publish';

        $('#cog_internal_output').html("Fetching internal link suggestions...");
    
        try {
            // Function to fetch all posts without pagination
            async function fetchAllPosts(page = 1, allPosts = []) {
                const response = await wp.apiRequest({
                    path: `/wp/v2/posts?status=${includeDrafts}&per_page=100&page=${page}&_fields=id,slug,content`,
                    method: 'GET'
                });
    
                allPosts = [...allPosts, ...response];
    
                // If response has 100 items, there might be more pages
                if (response.length === 100) {
                    return fetchAllPosts(page + 1, allPosts);
                }
    
                return allPosts;
            }
    
            let allPosts = await fetchAllPosts();
            console.log("All Posts:", allPosts);
        
            if($("#just_orphan").is(":checked")) {
                let allUrls = allPosts.map(post => '"' + window.location.origin + "/" + post.slug + '"');
            
                let linkedPosts = new Set();
            
                // Loop through each post and check if it links to other posts
                allPosts.forEach(post => {
                    allUrls.forEach(url => {
                        allPosts.forEach(post => {
                            allUrls.forEach(url => {
                                const regex = new RegExp(url + "(?!#)", "g"); // Match only the post URL, ignoring #section
                                if (post.content.rendered.replace(/#\S+/g, "").includes(url)) { 
                                    linkedPosts.add(url);
                                }
                            });
                        });
                    });
                });
            
                // Find posts that are NOT linked anywhere
                allPosts = allPosts.filter(post => !linkedPosts.has('"' + window.location.origin + "/" + post.slug + '"'));
            
                console.log("Orphan Articles:", allPosts);
            }
            allPosts = allPosts.map(post => ({
                slug: post.slug
            }));
            // Send orphan articles for link suggestions
            $.ajax({
                url: cog_params.ajax_url,
                method: 'POST',
                data: {
                    action: 'fetch_internal_links',
                    article: article,
                    posts: JSON.stringify(allPosts), // Send only orphan posts
                    link: window.location.origin
                },
                success: function (response) {
                    if (response.success) {
                        const links = JSON.parse(cleanContent(response.data.links.replace('```json', '').replace('```', '')).replace(/\\'/g, "'"));
                        $('#cog_internal_links_output').removeAttr('hidden');
    
                        let linksHtml = "";
                        links.forEach((link, index) => {
                            const button = `<button onclick="insertLinkIntoArticle(${index})" class="set-button icon-button" style=" color: white; border: none; padding: 8px 16px; cursor: pointer; font-size: 0.875rem; transition: background-color 0.3s;">
                            <i class="fas fa-plus"></i>
                            </button>`;
    
                            linksHtml += `
                            <div id="link-item-${index}" class="link-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 8px; background-color: #f9f9f9; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);">
                                <div class="link-header" style="display: flex; justify-content: space-between; align-items: center;">
                                    <h4 style="font-size: 1.25rem; color: #333;">${link.slug}</h4>
                                    ${button}
                                </div>
                                <div class="custom-admin-notice" id="custom-admin-notice-${index}"></div>
                                <ul class="link-details" style="list-style-type: none; padding-left: 0; margin-top: 10px; color: #555;">
                                    <li id="internallink-link-${index}"><strong>Link:</strong> <a href="${window.location.origin}/${link.slug}" target="_blank" style="color: #007bff; text-decoration: none;">${window.location.origin}/${link.slug}</a></li>
                                    <li><strong>Title:</strong> ${link.title}</li>
                                    <li><strong>Sentence:</strong> ${link.sentence}</li>
                                    <li id="internallink-old-${index}"><strong>Current Paragraph:</strong> ${link.old_paragraph}</li>
                                    <li id="internallink-new-${index}"><strong>New Paragraph:</strong> ${link.new_paragraph}</li>
                                </ul>
                            </div>`;
                        });
    
                        $('#cog_internal_links_output').html(linksHtml);
                        $('#cog_internal_output').html("Links fetched successfully");
    
                    } else {
                        $('#cog_internal_output').html("Error: " + response.data.message);
                    }
                },
                error: function (response) {
                    console.log(response);
                    $('#cog_internal_output').html("Error fetching internal links.");
                }
            });
    
        } catch (error) {
            console.error("Error fetching posts or internal links:", error);
            $('#cog_internal_output').html("Error fetching posts or internal links.");
        }
    });
    
        function highlightAndScrollToModifiedParagraph(paragraph) {    
            const blockElem = document.querySelector(`[data-block="${paragraph.clientId}"]`);
            if (blockElem) {
                blockElem.scrollIntoView({ behavior: 'smooth', block: 'center' });

                // Highlight the modified paragraph
                blockElem.style.backgroundColor = 'yellow';
                setTimeout(() => blockElem.style.backgroundColor = '', 2000);
            } else {
                console.warn("❌ Target block not found in the DOM.");
            }
        }
        
        window.insertLinkIntoArticle = function(index) {
            // Get the current post content
            const postContent = wp.data.select('core/editor').getEditedPostContent();
            const oldParagraphElem = $('#internallink-old-' + index)[0]; 
            const newParagraphElem = $('#internallink-new-' + index)[0]; 

            if (oldParagraphElem && newParagraphElem) {
                // Get all child nodes after <strong>
                const old_paragraph = [...oldParagraphElem.childNodes]
                    .slice(1) // Ignore <strong>
                    .map(node => node.outerHTML || node.nodeValue) // Get text and HTML
                    .join('')
                    .trim();

                const paragraph = [...newParagraphElem.childNodes]
                    .slice(1) // Ignore <strong>
                    .map(node => node.outerHTML || node.nodeValue)
                    .join('')
                    .trim();
                    const blocks = wp.data.select('core/block-editor').getBlocks(); // Get all blocks from the editor

                    let blockFound = false;
                    
                    const updatedBlocks = blocks.map(block => {
                        if (block?.attributes?.content?.text?.includes(old_paragraph)) {
                            blockFound = true;
                            setTimeout(() => highlightAndScrollToModifiedParagraph(block), 1000);
                            return {
                                ...block,
                                attributes: {
                                    ...block.attributes,
                                    content: block.attributes.content.text.replace(old_paragraph, paragraph) // Replace only paragraph
                                }
                            };
                        }
                        return block;
                    });
                
                    if (blockFound) {
                        // Update only the modified blocks
                        wp.data.dispatch('core/block-editor').resetBlocks(updatedBlocks);
                        wp.data.dispatch('core/editor').savePost();
                        $('#link-item-'+index).css({
                            "background-color": '#e3dddd'
                        })
                        $(`#custom-admin-notice-${index}`).text("Paragraph replaced successfully.");
                        $(`#custom-admin-notice-${index}`).css("background-color", "rgb(138 175 139)");
                        $(`#custom-admin-notice-${index}`).css("color", "white");
                    } else {
                        $(`#custom-admin-notice-${index}`).text("❌ Old paragraph not found in any block.");
                        $(`#custom-admin-notice-${index}`).css("background-color", "rgb(254 158 151)");
                        $(`#custom-admin-notice-${index}`).css("color", "white");
                    }
            }
        }
    const processNode = (node, meta) => {
        let block;
        if (node.tagName.match(/^H[1-6]$/i)) {
            // Heading block
            if(node.tagName.replace('H', '') == '1') {
                wp.data.dispatch('core/editor').editPost({ title:  cleanContent(node.textContent.trim()), excerpt: meta });
            } else {    
                block = (wp.blocks.createBlock('core/heading', {
                    content:  cleanContent(node.textContent.trim()),
                    level: parseInt(node.tagName.replace('H', ''), 10)
                }));
            }
        } else if (node.tagName.toLowerCase() === 'p') {
            // Paragraph block
            block = (wp.blocks.createBlock('core/paragraph', {
                content:  cleanContent(node.innerHTML.trim())
            }));
        } else if (node.tagName.toLowerCase() === 'ul' || node.tagName.toLowerCase() === 'ol') {
            // List block
            const items = Array.from(node.querySelectorAll('li')).map(li => li.innerHTML.trim());
            block = (wp.blocks.createBlock('core/list', {
                values: items.map(item => `<li>${cleanContent(item)}</li>`).join(''),
                ordered: node.tagName.toLowerCase() === 'ol'
          }));
        } else if (node.tagName.toLowerCase() === 'table') {
            const rows = Array.from(node.querySelectorAll('tr')).map(row => {
                const cells = Array.from(row.children).map(cell => {
                    const tag = cell.tagName.toLowerCase();
                    const content = cell.innerHTML.trim();
                    return `<${tag}>${content}</${tag}>`;
                }).join('');
                return `<tr>${cells}</tr>`;
            }).join('');
            block = (wp.blocks.createBlock('core/html', {
                content: `<table>${rows}</table>`
            }));
        } else if(node.tagName.toLowerCase() === 'faqs') {
             // Look for the <faqs> tag inside the node
             const faqsNode = node;
             if (faqsNode) {
                 const faqs = JSON.parse(faqsNode.textContent);
 
                 const faqData = faqs.map(faqItem => ({
                     title: cleanContent(faqItem['title']),
                     content: cleanContent(faqItem['content']),
                     visible:'true'
                 }));
                 // Create the FAQ block with the extracted data
                 block = wp.blocks.createBlock('rank-math/faq-block', {
                     questions: faqData
                 });
            }
            
        } else if(node.tagName.toLowerCase() === 'b' || node.tagName.toLowerCase() === 'strong' ) {
            block = (wp.blocks.createBlock('core/paragraph', {
                content:  `<strong>`+cleanContent(node.textContent.trim()) +`</strong>` 
            }));
        }
        else if(node.tagName.toLowerCase() === 'em' || node.tagName.toLowerCase() === 'i' ) {
            block = (wp.blocks.createBlock('core/paragraph', {
                content:  `<em>`+cleanContent(node.textContent.trim()) +`</em>` 
            }));
        }
        return block
    };
    async function updateRankMathMeta(title, description, focusKeyword, slug) {
        try {
            wp.data.dispatch('rank-math').updateDescription(description)
            wp.data.dispatch('rank-math').updatePermalink(slug)
            wp.data.dispatch('rank-math').updateKeywords(focusKeyword)
            wp.data.dispatch('rank-math').updateTitle(title)
        } catch (error) {
            console.error("Error:", error);
        }
    }

    // Call the function with your values


    $('#cog_generate_button').on('click', function () {
        const topic = $('#cog_topic').val();
        const blockType = $('#cog_block_type').val();
        if (!topic) {
            alert("Please enter a topic.");
            return;
        }
        $('#cog_output').html("Generating outline...");
        $.ajax({
            url: cog_params.ajax_url,
            method: 'POST',
            data: {
                action: 'generate_custom_outline',
                topic: topic,
                blockType: blockType
            },
            success: function (response) {
                if (response.success) {
                    $('#cog_output').html("Outline generated successfully!");
                    let lines = response.data.outline.split('\n');
                    let blocks = [];
                    // Dispatch the editPost action to update the title
                    wp.data.dispatch('core/editor').editPost({ title: response.data.title.trim()});
                    for (let index = 0; index < lines.length; index++) {
                        const line = lines[index].trim();
                        if (line != "") {
                            let r = line.split('|');
                            blocks.push(wp.blocks.createBlock('core/heading', {
                                content: r[1],
                                level: r[0].length + 1,
                            }));
                        }
                    }
                    wp.data.dispatch('core/block-editor').insertBlocks(blocks);
                    wp.data.dispatch('core/editor').savePost();
                    updateRankMathMeta(response.data.title.trim(), response.data.meta.trim(), response.data.keywords.trim());
                } else {
                    $('#cog_output').html("Error: " + response.data.message);
                }
            },
            error: function () {
                $('#cog_output').html("Error generating outline.");
            }
        });
    });

    $('#cog_upload_image_button').on('click', function () {
        $('#cog_image_output').html('Uploading...');
        const imageUrl = $('#cog_image_url').val();
        const alt = $('#image_alt_text').val();
        $.post(cog_params.ajax_url, { action: 'cog_upload_image_from_url', image_url: imageUrl, alt: alt }, function (response) {
            if (response.success) {
                const imageBlock = wp.blocks.createBlock('core/image', {
                    id: response.data.attachment_id,
                    url: response.data.attachment_url,
                    alt: alt
                });
                const selectedBlockClientId = wp.data.select('core/block-editor').getSelectedBlockClientId();

                if (selectedBlockClientId) {
                    // Get the index of the selected block
                    const selectedBlockIndex = wp.data.select('core/block-editor').getBlockIndex(selectedBlockClientId);
                    // Handle potential errors (block might not exist)
                    if (selectedBlockIndex !== -1) {
                        // Insert the image block after the selected block at the correct index
                        wp.data.dispatch('core/block-editor').insertBlock(imageBlock, selectedBlockIndex + 1);
                    } else {
                        console.warn('Selected block not found. Inserting at the end.');
                        wp.data.dispatch('core/block-editor').insertBlock(imageBlock);
                    }
                } else {
                    // Insert at the end if no block is selected
                    wp.data.dispatch('core/block-editor').insertBlock(imageBlock);
                }
                $('#cog_image_output').html(`Image uploaded!`);
            } else {
                $('#cog_image_output').html('Image upload failed: ' + response.data.message);
            }
        });
    });
    $('#cog_generate_image_button').on('click', function () {
        const selectedBlockClientId = wp.data.select('core/block-editor').getSelectedBlockClientId();
        let title = wp.data.select('core/editor').getEditedPostAttribute('title');
        const meta = wp.data.select('core/editor').getEditedPostAttribute('excerpt');
        if (selectedBlockClientId) {
            // Get the selected block using its Client ID
            const selectedBlock = wp.data.select('core/block-editor').getBlock(selectedBlockClientId);

            if (selectedBlock) {
                title = selectedBlock.attributes.content.text
            }
        }
        let ratio = '16:9';
        let selectedRatio  = $('input[name="cog_image_ratio"]:checked').val();
        if (selectedRatio == '11') {
            ratio = '1:1';
        } else if (selectedRatio == '43') {
            ratio = '4:3';
        }
                    
            
        $('#cog_ai_image_output').html('Generating...');
        $.post(cog_params.ajax_url, { action: 'cog_generate_image_from_ai', title: title, meta: meta , ratio : ratio }, function (response) {
            if (response.success) {

                $('#cog_ai_image_output').html('<img onclick="showImageOverlay(this.src)" class="outline_img" src = "' + response.data.image_url + '" alt="' + response.data.alt + '" />');
                $('#cog_image_url').val(response.data.image_url);
                $('#image_alt_text').val(response.data.alt.trim());

            } else {
                $('#cog_ai_image_output').html('Image upload failed: ' + response.data.message);
            }
        });
    });

    $('#cog_upload_featured_image_button').on('click', function () {
        $('#cog_image_output').html('Uploading...');
        const imageUrl = $('#cog_featured_image_url').val();
        const alt = $('#image_featured_alt_text').val();
        $.post(cog_params.ajax_url, { action: 'cog_upload_image_from_url', image_url: imageUrl, alt: alt }, function (response) {
            if (response.success) {
                wp.data.dispatch('core/editor').editPost({ featured_media: response.data.attachment_id });
                wp.data.dispatch('core/editor').savePost();
                $('#cog_ai_featured_image_output').html(`Image uploaded!`);
            } else {
                $('#cog_ai_featured_image_output').html('Image upload failed: ' + response.data.message);
            }
        });
    });

    $('#cog_generate_featured_image_button').on('click', function () {
        const title = wp.data.select('core/editor').getEditedPostAttribute('title');
        const meta = wp.data.select('core/editor').getEditedPostAttribute('excerpt');

        $('#cog_ai_featured_image_output').html('Generating...');
        $.post(cog_params.ajax_url, { action: 'cog_generate_image_from_ai', title: title, meta: meta }, function (response) {
            if (response.success) {

                $('#cog_ai_featured_image_output').html('<img onclick="showImageOverlay(this.src)" class="outline_img" src = "' + response.data.image_url + '" alt="' + response.data.alt + '" />');
                $('#cog_featured_image_url').val(response.data.image_url);
                $('#image_featured_alt_text').val(response.data.alt.trim());

            } else {
                $('#cog_ai_featured_image_output').html('Image upload failed: ' + response.data.message);
            }
        });
    });

    $('#cog_generate_text_button').on('click', async function () {
        $('#cog_text_output').html('Generating...');

        const selectedBlockClientId = wp.data.select('core/block-editor').getSelectedBlockClientId();
        let title = wp.data.select('core/editor').getEditedPostAttribute('title');
        const meta = wp.data.select('core/editor').getEditedPostAttribute('excerpt');
        const blocks = wp.data.select('core/block-editor').getBlocks();

        // Filter blocks to find all level 2 headings
        let txt = "";
        let currentHeading = "";

    
        const headings = blocks.filter(block => block.name === 'core/heading');

        for (let i = 0; i < headings.length; i++) {
            const heading = headings[i];
            const isLastHeading = i === headings.length - 1;

            if (heading.attributes.level === 2 && i != 0 && !isLastHeading) {
                $('#cog_text_output').html('Generating: ' + currentHeading.split('|')[2]);
                try {
                    const response = await makePostRequest({
                        action: 'generate_paragraphs',
                        sections: txt,
                        title: title,
                        meta: meta
                    });

                    const lines = response.data.paragraphs.split('####');
                    for (let index = 0; index < lines.length; index++) {
                        const line = lines[index].trim();
                        if (line != "") {
                            let r = line.split('|');
                            const selectedBlockIndex = wp.data.select('core/block-editor').getBlockIndex(r[0]);
                            const paragraph = wp.blocks.rawHandler({ HTML: r[1] });

                            // Insert the paragraph block after the selected block at the correct index
                            wp.data.dispatch('core/block-editor').insertBlocks(paragraph, selectedBlockIndex + 1);
                        }
                    }
                } catch (error) {
                    $('#cog_text_output').html("Error in POST request:" + error);
                    txt = "";
                }
                txt = "";
            }

            if (heading.attributes.level === 2) {
                txt = `[${heading.clientId}|${heading.attributes.level}|${heading.attributes.content.text}],`;
                currentHeading = txt;
            }
            if (heading.attributes.level > 2) {
                txt += `[${heading.clientId}|${heading.attributes.level}|${heading.attributes.content.text}],`;
            }
            if (isLastHeading) {
                $('#cog_text_output').html('Generating: ' + currentHeading.split('|')[2]);
                try {
                    const response = await makePostRequest({
                        action: 'generate_paragraphs',
                        sections: txt,
                        title: title,
                        meta: meta
                    });

                    const lines = response.data.paragraphs.split('####');
                    for (let index = 0; index < lines.length; index++) {
                        const line = lines[index].trim();
                        if (line != "") {
                            let r = line.split('|');
                            const selectedBlockIndex = wp.data.select('core/block-editor').getBlockIndex(r[0]);
                            const paragraph = wp.blocks.rawHandler({ HTML: r[1] });

                            // Insert the paragraph block after the selected block at the correct index
                            wp.data.dispatch('core/block-editor').insertBlocks(paragraph, selectedBlockIndex + 1);
                        }
                        $('#cog_text_output').html("Finished genearation paragraph");
                    }
                } catch (error) {
                    $('#cog_text_output').html("Error in POST request:" + error);
                    txt = "";
                }
                txt = "";
            }

        }

    });

    function makePostRequest(data) {
        return new Promise((resolve, reject) => {
            $.post(cog_params.ajax_url, data, function (response) {
                if (response.success) {
                    resolve(response);
                } else {
                    reject(new Error("Request failed"));
                }
            }).fail(reject);
        });
    }
   


$('#cog_generate_selected_text_button').on('click', async function () {
        const title = wp.data.select('core/editor').getEditedPostAttribute('title');
        const meta = wp.data.select('core/editor').getEditedPostAttribute('excerpt');
        const currentHeadingBlock =  wp.data.select('core/block-editor').getSelectedBlock();

        
        if (currentHeadingBlock.name === 'core/heading') {
            // Get the selected block using its Client ID
                $('#cog_text_output').html('Generating: ' + currentHeadingBlock.attributes.content.text);
                try {
                    const response = await makePostRequest({
                        action: 'generate_paragraphs',
                        sections: `[${currentHeadingBlock.clientId}|${currentHeadingBlock.attributes.level}|${currentHeadingBlock.attributes.content.text}]`,
                        title: title,
                        meta: meta
                    });

                    const lines = response.data.paragraphs.split('####');
                    for (let index = 0; index < lines.length; index++) {
                        const line = lines[index].trim();
                        if (line != "") {
                            let r = line.split('|');
                            const selectedBlockIndex = wp.data.select('core/block-editor').getBlockIndex(r[0]);
                            const paragraph = wp.blocks.rawHandler({ HTML: r[1] });

                            // Insert the paragraph block after the selected block at the correct index
                            wp.data.dispatch('core/block-editor').insertBlocks(paragraph, selectedBlockIndex + 1);
                        }
                    }
                } catch (error) {
                    $('#cog_text_output').html("Error in POST request:" + error);
                }

            
        } else {
            $('#cog_text_output').html("Please select a title in the editor");
        }            

    });
    $('#cog_generate_images_button').on('click', async function () {
        const article = wp.data.select('core/editor').getEditedPostContent();
        
        const topic = wp.data.select('rank-math').getKeywords().split(',')[0];
        if (!article) {
            alert("Please generate the article.");
            return;
        }
        if (!topic) {
            alert("Please enter a topic.");
            return;
        }
        $('#cog_images_output').html("Processing Images prompts...");

        $.ajax({
            url: cog_params.ajax_url,
            method: 'POST',
            data: {
                action: 'fetch_images',
                article: article,
                topic: topic
            },

            success: function (response) {
                if (response.success) {

                    const imageDetails = JSON.parse(cleanContent(response.data.images.replace('```json', '').replace('```','')));
                    let imagesHtml = "<table><tr><th>Midjourney Prompt</th><th>Alt Text</th><th>Title</th><th>Caption</th><th>Description</th><th>Emplacment</th><th>Actions</th></tr>"
                    imageDetails.map((image, index) => {
                        imagesHtml += `
                            <tr> 
                                <td id="midjourney-prompt-${index}">
                                    ${image.MidjourneyPrompt}
                                    <button class="copy-button">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>                                <td id="image-alt-${index}">${image.AltText}</td>
                                <td id="image-title-${index}">${image.Title}</td>
                                <td id="image-caption-${index}">${image.Caption}</td>
                                <td id="image-description-${index}">${image.Description}</td>
                                <td id="image-emplacement-${index}">${image.Emplacement}</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="icon-button browse-button" onclick="document.getElementById('file-input-${index}').click()">
                                            <i class="fas fa-folder-open"></i>
                                        </button>
                                        <input type="file" id="file-input-${index}" class="file-input" style="display: none;" onchange="previewFile(event, ${index})" accept="image/*"  />
                                        <button class="icon-button upload-button" onclick="uploadImage(${index})">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <div class="preview-container" id="preview-container-${index}">
                                        <img id="preview-image-${index}" class="preview-image" src="" alt="Image Preview" style="display: none;" />
                                        <p class="upload-status"></p>
                                    </div>
                                </td>
                            </tr> 
                        `;

                    })
                    imagesHtml += '</table>'
                    $('#cog_images_result_output').html(imagesHtml);
                    $('#cog_images_output').html(" Image prompts generated successfully ");
                    wp.data.dispatch('core/editor').savePost();

                } else {
                    $('#cog_images_output').html("Error: " + response.data.message);
                }
            },
            error: function (response) {
                console.log(response)
                $('#cog_images_output').html("Error fetching internal links.");
            }
        });

        })
        $(document).on('click', '.copy-button', function() {
            // Get text from the closest <td> element
            let textToCopy = $(this).closest('td').clone() // Clone to avoid modifying original
                                .children('button') // Select button inside it
                                .remove() // Remove the button
                                .end() // Go back to cloned <td>
                                .text().trim(); // Get text content
        
            // Create a temporary input field to copy the text
            let $tempInput = $('<textarea>');
            $('body').append($tempInput);
            $tempInput.val(textToCopy).select();
            document.execCommand('copy');
            $tempInput.remove();
        
            // Show confirmation icon
            let $button = $(this);
            $button.html('<i class="fas fa-check"></i>');
        
            setTimeout(() => {
                $button.html('<i class="fas fa-copy"></i>');
            }, 2000);
        });
        const form = $('#api-settings');

        const apiKeyInput = form.find("input[name='cog_settings[gemini_api_key]']");
                const modelSelect = form.find("select[name='cog_settings[ai_model]']");
                const gptModel = form.find("select[name='cog_settings[gpt_choice]']");
                window.fetchModels = async function(apiKey, gptModel) {
                    if (!apiKey) {
                        console.error("API key is required.");
                        return;
                    }
                
                    // Build the API URL based on the gptModel value
                    let apiUrl = '';
                    let response = ""
                    let models = ""
                    if (gptModel === 'gemini') {
                        apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models?key=' + apiKey;
                         response = await fetch(apiUrl);
                         models = await response.json()
                        updateModelOptions(models.models || [], gptModel);
                    } else if (gptModel === 'claude') {

                        apiUrl = 'https://api.anthropic.com/v1/models';
                        $.ajax({
                            url: cog_params.ajax_url,
                            method: 'POST',
                            data: {
                                action: 'fetch_claude_models',
                                api_key: apiKey,
                            },
                            success: function (response) {
                                models = response.data.claudeModels.data
                                updateModelOptions(models || [], gptModel);
                            }})
                    } else {
                        console.error('Unsupported model type:', gptModel);
                        return;
                    }

                };
                
                function updateModelOptions(models, gptModel) {
                    const val = modelSelect.attr('value')
                    modelSelect.empty().append('<option value="">Select an AI Model</option>');
                    if (models.length) {
                        models.forEach(model => {
                            const selected = val == (model?.name?.replace('models/', '')  ?? model.id)
                            if(model?.supportedGenerationMethods?.find((method) => method == "generateContent") || gptModel == 'claude')
                            {
                                modelSelect.append($('<option>', { value: model?.name?.replace('models/', '') ?? model.id, text: model.displayName ?? model.display_name, selected: selected}));
                            }
                        });
                    }

                 
                }

                apiKeyInput.on('change', function () {
                    fetchModels(apiKeyInput.val(), gptModel.val());
                });
                modelSelect.on('change', function () {
                    var selectedValue = $(this).val(); // Get the selected value
                    $(this).find("option").each(function () {
                        $(this).attr("selected", $(this).val() === selectedValue); // Ensure only the chosen option is selected
                    });
                });
                gptModel.on('change', function () {
                    fetchModels(apiKeyInput.val(), gptModel.val());
                });

                if (apiKeyInput.val()) {
                    fetchModels(apiKeyInput.val(), gptModel.val());
                }
})(window.wp, jQuery);



function showImageOverlay(imageUrl) {
    const overlay = document.createElement('div');
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100%';
    overlay.style.height = '100%';
    overlay.style.background = 'rgba(0,0,0,0.5)';
    overlay.style.display = 'flex';
    overlay.style.justifyContent = 'center';
    overlay.style.alignItems = 'center';
    overlay.style.zIndex = '1000';
    const div = document.createElement('div');
        div.style.width = '62%';
    const image = document.createElement('img');
    image.src = imageUrl;
    image.style.width = '100%';
    div.appendChild(image);

    const closeButton = document.createElement('button');
    closeButton.textContent = 'Close';
    closeButton.style.position = 'relative';
    closeButton.style.bottom = '52px';
    closeButton.style.right = '10px';
    closeButton.style.background = 'white';
    closeButton.style.border = 'none';
    closeButton.style.padding = '10px';
    closeButton.style.cursor = 'pointer';
    closeButton.addEventListener('click', () => overlay.remove());

    overlay.appendChild(div);
    overlay.appendChild(closeButton);
    document.body.appendChild(overlay);
}
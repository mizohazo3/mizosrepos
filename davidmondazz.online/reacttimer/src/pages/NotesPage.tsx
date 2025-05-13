import React, { useState, useEffect, useCallback, useRef } from 'react';
import { 
  Box, 
  Container, 
  Heading, 
  Text, 
  Button, 
  useColorModeValue, 
  VStack, 
  HStack, 
  Input,
  InputGroup,
  InputLeftElement,
  InputRightElement,
  Badge,
  SimpleGrid,
  Flex,
  IconButton,
  Divider,
  Tooltip,
  useToast,
  Modal,
  ModalOverlay,
  ModalContent,
  ModalHeader,
  ModalBody,
  ModalFooter,
  ModalCloseButton,
  FormControl,
  FormLabel,
  Skeleton,
  useDisclosure,
  AlertDialog,
  AlertDialogBody,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogContent,
  AlertDialogOverlay
} from '@chakra-ui/react';
import { FaPlus, FaSearch, FaEdit, FaTrash, FaSave, FaImage, FaCode, FaCopy, FaThumbtack, FaTimes } from 'react-icons/fa';
import { TimerService } from '../services/firebase';
import { Note } from '../types';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css';

const NotesPage: React.FC = () => {
  // All useStates first
  const [notes, setNotes] = useState<Note[]>([]);
  const [filteredNotes, setFilteredNotes] = useState<Note[]>([]);
  const [selectedNote, setSelectedNote] = useState<Note | null>(null);
  const [noteToDelete, setNoteToDelete] = useState<Note | null>(null);
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [fullViewNote, setFullViewNote] = useState<Note | null>(null);
  
  // All useDisclosure hooks next
  const { isOpen, onOpen, onClose } = useDisclosure();
  const { 
    isOpen: isDeleteAlertOpen, 
    onOpen: onDeleteAlertOpen, 
    onClose: onDeleteAlertClose 
  } = useDisclosure();
  const {
    isOpen: isFullViewOpen,
    onOpen: onFullViewOpen,
    onClose: onFullViewClose
  } = useDisclosure();
  
  // All useRefs after
  const cancelRef = useRef<HTMLButtonElement>(null);
  const timerService = useRef(new TimerService());
  const unsubscribeRef = useRef<(() => void) | null>(null);
  const quillRef = useRef<ReactQuill>(null);
  
  // All other hooks at the end
  const toast = useToast();

  // Colors
  const bgColor = useColorModeValue('white', 'gray.800');
  const borderColor = useColorModeValue('gray.200', 'gray.700');
  const noteBgColor = useColorModeValue('gray.50', 'gray.700');
  const selectedNoteBgColor = useColorModeValue('blue.50', 'blue.900');
  const hoverBgColor = useColorModeValue('blue.50', 'rgba(66, 153, 225, 0.1)');
  const pinnedBgColor = useColorModeValue('green.50', 'green.900');
  
  // Load notes from Firebase
  useEffect(() => {
    const loadNotes = async () => {
      try {
        unsubscribeRef.current = timerService.current.subscribeToNotes((fetchedNotes) => {
          setNotes(fetchedNotes);
          setFilteredNotes(fetchedNotes);
          setIsLoading(false);
        });
      } catch (error) {
        console.error('Error loading notes:', error);
        toast({
          title: 'Error loading notes',
          status: 'error',
          duration: 3000,
          isClosable: true,
        });
        setIsLoading(false);
      }
    };

    loadNotes();

    // Cleanup subscription on unmount
    return () => {
      if (unsubscribeRef.current) {
        unsubscribeRef.current();
      }
    };
  }, [toast]);

  // Filter notes when search query changes
  useEffect(() => {
    if (!searchQuery.trim()) {
      setFilteredNotes(notes);
      return;
    }

    // Split the search query into individual terms
    const searchTerms = searchQuery.toLowerCase().split(/\s+/).filter(term => term.length > 0);
    
    const filtered = notes.filter((note) => {
      // Check if the note matches all search terms
      return searchTerms.every(term => {
        // Convert content to lowercase for case-insensitive search
        const title = note.title.toLowerCase();
        const content = note.content.toLowerCase();
        const tags = note.tags ? note.tags.map(tag => tag.toLowerCase()) : [];
        
        // Create a regex pattern to match beginning of words
        const pattern = new RegExp(`\\b${term}`, 'i');
        
        // Check if the term exists at the beginning of any word in title, content, or tags
        return (
          pattern.test(title) || 
          pattern.test(content) || 
          tags.some(tag => pattern.test(tag))
        );
      });
    });
    
    setFilteredNotes(filtered);
  }, [searchQuery, notes]);

  // Create a new note
  const handleCreateNote = useCallback(() => {
    setSelectedNote(null);
    setTitle('');
    setContent('');
    onOpen();
  }, [onOpen]);

  // Edit a note
  const handleEditNote = useCallback((note: Note) => {
    setSelectedNote(note);
    setTitle(note.title);
    setContent(note.content);
    onOpen();
  }, [onOpen]);

  // Format date for display
  const formatDate = (timestamp: number) => {
    return new Date(timestamp).toLocaleString();
  };

  // Format content, including JSON detection and formatting
  const formatContent = useCallback((content: string) => {
    // First remove HTML tags
    const plainText = content.replace(/<[^>]*>|&nbsp;/g, ' ').replace(/\s+/g, ' ').trim();
    
    // Try to detect if this is JSON content
    if (plainText.trim().startsWith('{') && plainText.trim().endsWith('}')) {
      try {
        // Try to parse and re-stringify with indentation
        const jsonObj = JSON.parse(plainText.replace(/&quot;/g, '"'));
        return JSON.stringify(jsonObj, null, 4);
      } catch (e) {
        // If parsing fails, just return the plain text
        return plainText;
      }
    }
    
    return plainText;
  }, []);

  // Delete a note
  const handleDeleteNote = useCallback(async (note: Note) => {
    setNoteToDelete(note);
    onDeleteAlertOpen();
  }, [onDeleteAlertOpen]);

  // Confirm delete note
  const confirmDeleteNote = useCallback(async () => {
    if (!noteToDelete) return;
    
    try {
      await timerService.current.deleteNote(noteToDelete.id);
      
      toast({
        title: 'Note deleted',
        status: 'success',
        duration: 3000,
        isClosable: true,
      });
    } catch (error) {
      console.error('Error deleting note:', error);
      toast({
        title: 'Error deleting note',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
    } finally {
      setNoteToDelete(null);
      onDeleteAlertClose();
    }
  }, [noteToDelete, toast, onDeleteAlertClose]);

  // Toggle pin status for a note
  const togglePinNote = useCallback(async (note: Note, e: React.MouseEvent) => {
    e.stopPropagation();
    
    try {
      // Toggle the pinned status in Firebase
      const isPinned = note.pinned || false;
      await timerService.current.updateNote(note.id, {
        pinned: !isPinned
      });
      
      toast({
        title: isPinned ? 'Note unpinned' : 'Note pinned',
        status: isPinned ? 'info' : 'success',
        duration: 2000,
        isClosable: true,
      });
    } catch (error) {
      console.error('Error updating pin status:', error);
      toast({
        title: 'Error updating note',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
    }
  }, [toast, timerService]);

  // Copy note content to clipboard
  const handleCopyContent = useCallback((note: Note, e: React.MouseEvent) => {
    e.stopPropagation();
    
    // Extract text content while preserving line breaks
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = note.content;
    
    // Process the HTML to maintain basic formatting for plain text
    const processNode = (node: Node): string => {
      // Text node - return its content
      if (node.nodeType === Node.TEXT_NODE) {
        return node.textContent || '';
      }
      
      // Handle various HTML elements that affect formatting
      if (node.nodeType === Node.ELEMENT_NODE) {
        const element = node as Element;
        const tagName = element.tagName.toLowerCase();
        
        // Add line breaks before and after block elements
        const isBlockElement = ['div', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote', 'pre'].includes(tagName);
        
        let result = isBlockElement ? '\n' : '';
        
        // Process all child nodes
        for (let i = 0; i < node.childNodes.length; i++) {
          result += processNode(node.childNodes[i]);
        }
        
        // Special handling for specific tags
        if (tagName === 'br') result += '\n';
        if (tagName === 'li') result += '\n';
        if (tagName === 'pre' || tagName === 'code') result = result.trimRight(); // don't add extra linebreaks
        
        // Add line break after block elements
        if (isBlockElement) result += '\n';
        
        return result;
      }
      
      // Process all other node types recursively
      let result = '';
      for (let i = 0; i < node.childNodes.length; i++) {
        result += processNode(node.childNodes[i]);
      }
      return result;
    };
    
    const formattedText = processNode(tempDiv).trim();
    
    // Copy the formatted text
    const textArea = document.createElement('textarea');
    textArea.value = formattedText;
    document.body.appendChild(textArea);
    textArea.select();
    
    try {
      document.execCommand('copy');
      toast({
        title: 'Content copied to clipboard with formatting preserved',
        status: 'success',
        duration: 2000,
        isClosable: true,
      });
    } catch (err) {
      console.error('Failed to copy text: ', err);
      toast({
        title: 'Failed to copy text',
        status: 'error',
        duration: 2000,
        isClosable: true,
      });
    } finally {
      document.body.removeChild(textArea);
    }
  }, [toast]);

  // Copy code block to clipboard
  const handleCopyCodeBlock = useCallback((content: string, e: React.MouseEvent) => {
    e.stopPropagation();
    const formattedContent = formatContent(content);
    
    // Use a textArea instead of directly using clipboard API to preserve formatting
    const textArea = document.createElement('textarea');
    textArea.value = formattedContent;
    document.body.appendChild(textArea);
    textArea.select();
    
    try {
      document.execCommand('copy');
      toast({
        title: 'Code copied to clipboard with formatting preserved',
        status: 'success',
        duration: 2000,
        isClosable: true,
      });
    } catch (err) {
      console.error('Failed to copy code: ', err);
      toast({
        title: 'Failed to copy code',
        status: 'error',
        duration: 2000,
        isClosable: true,
      });
    } finally {
      document.body.removeChild(textArea);
    }
  }, [formatContent, toast]);

  // Handle image paste
  const handleImagePaste = useCallback((e: ClipboardEvent) => {
    if (!quillRef.current) return;
    
    const clipboard = e.clipboardData;
    if (!clipboard || !clipboard.items) return;

    const items = clipboard.items;
    
    for (let i = 0; i < items.length; i++) {
      if (items[i].type.indexOf('image') === -1) continue;
      
      e.preventDefault();
      
      const file = items[i].getAsFile();
      if (!file) continue;

      const reader = new FileReader();
      reader.onload = (event) => {
        const result = event.target?.result as string;
        const quill = quillRef.current?.getEditor();
        if (quill) {
          const range = quill.getSelection(true);
          quill.insertEmbed(range.index, 'image', result);
        }
      };
      reader.readAsDataURL(file);
      break;
    }
  }, []);

  // Setup paste event listener when editor is active
  useEffect(() => {
    if (isOpen) {
      document.addEventListener('paste', handleImagePaste);
    }
    
    return () => {
      document.removeEventListener('paste', handleImagePaste);
    };
  }, [isOpen, handleImagePaste]);

  // Save a note
  const handleSaveNote = useCallback(async () => {
    if (!title.trim()) {
      toast({
        title: 'Title is required',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
      return;
    }
    
    setIsSaving(true);
    
    try {
      // Check if content contains images or code blocks
      const hasImages = content.includes('<img');
      const hasCodeSnippets = content.includes('<pre') || content.includes('code-block');
      
      // For new note
      if (!selectedNote) {
        await timerService.current.createNote({
          title,
          content,
          hasImages,
          hasCodeSnippets,
          pinned: false
        });
        
        toast({
          title: 'Note created',
          status: 'success',
          duration: 3000,
          isClosable: true,
        });
      } 
      // For existing note
      else {
        await timerService.current.updateNote(selectedNote.id, {
          title,
          content,
          hasImages,
          hasCodeSnippets,
          // Preserve existing pinned status
          pinned: selectedNote.pinned || false
        });
        
        toast({
          title: 'Note updated',
          status: 'success',
          duration: 3000,
          isClosable: true,
        });
      }
      
      onClose();
    } catch (error) {
      console.error('Error saving note:', error);
      toast({
        title: 'Error saving note',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
    } finally {
      setIsSaving(false);
    }
  }, [title, content, selectedNote, toast, onClose, timerService]);

  // Sort notes with pinned notes at the top
  const sortedNotes = useCallback((notes: Note[]) => {
    return [...notes].sort((a, b) => {
      // First sort by pinned status
      const aPinned = a.pinned ? 1 : 0;
      const bPinned = b.pinned ? 1 : 0;
      
      if (aPinned !== bPinned) {
        // Reverse the comparison to put pinned notes first
        return bPinned - aPinned;
      }
      
      // Then sort by date (newest first)
      return (b.updatedAt || 0) - (a.updatedAt || 0);
    });
  }, []);

  // Show full note content
  const handleShowFullNote = useCallback((note: Note, e: React.MouseEvent) => {
    e.stopPropagation();
    setFullViewNote(note);
    onFullViewOpen();
  }, [onFullViewOpen]);

  return (
    <Container maxW="container.xl" py={8}>
      <Flex justify="space-between" align="center" mb={6}>
        <Heading size="lg">Notes</Heading>
        <Button 
          leftIcon={<FaPlus />} 
          colorScheme="blue" 
          onClick={handleCreateNote}
        >
          New Note
        </Button>
      </Flex>

      <Flex justify="center" mb={6}>
        <InputGroup maxW="500px" size="lg">
          <InputLeftElement pointerEvents="none" h="100%">
            <FaSearch color="gray.300" />
          </InputLeftElement>
          <Input 
            placeholder="Search notes by title, content or tags..." 
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            h="50px"
            fontSize="md"
            borderRadius="lg"
          />
          {searchQuery && (
            <InputRightElement h="100%">
              <IconButton
                aria-label="Clear search"
                icon={<FaTimes />}
                size="sm"
                variant="ghost"
                onClick={() => setSearchQuery('')}
              />
            </InputRightElement>
          )}
        </InputGroup>
      </Flex>

      {isLoading ? (
        <VStack spacing={4} align="stretch">
          {[1, 2, 3].map((i) => (
            <Skeleton key={i} height="180px" />
          ))}
        </VStack>
      ) : filteredNotes.length > 0 ? (
        <SimpleGrid columns={{ base: 1, md: 2, lg: 3 }} spacing={6}>
          {sortedNotes(filteredNotes).map((note) => {
            const isPinned = note.pinned || false;
            const isCode = note.content.includes('{') && note.content.includes('}');
            
            return (
              <Box 
                key={note.id}
                height="400px"
                p={6}
                borderWidth="1px"
                borderRadius="xl"
                bg={isPinned ? pinnedBgColor : noteBgColor}
                borderColor={isPinned ? "green.300" : borderColor}
                boxShadow={isPinned ? "lg" : "md"}
                transition="all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)"
                _hover={{ 
                  transform: 'translateY(-8px) scale(1.02)', 
                  boxShadow: 'xl', 
                  borderColor: isPinned ? "green.400" : 'blue.400',
                  bg: isPinned ? pinnedBgColor : hoverBgColor
                }}
                position="relative"
                overflow="hidden"
                display="flex"
                flexDirection="column"
                role="group"
                cursor="default"
              >
                {/* Top gradient accent */}
                <Box 
                  position="absolute"
                  top="0"
                  left="0"
                  right="0"
                  height="4px"
                  bgGradient={isPinned 
                    ? "linear(to-r, green.400, green.600)"
                    : note.hasCodeSnippets 
                      ? "linear(to-r, purple.400, blue.500)" 
                      : note.hasImages 
                        ? "linear(to-r, green.400, teal.500)" 
                        : "linear(to-r, blue.400, cyan.500)"
                  }
                />
                
                <Flex direction="column" h="100%">
                  {/* Header with title and actions */}
                  <Flex justify="space-between" align="start" mb={3}>
                    <Heading 
                      size="md" 
                      noOfLines={1} 
                      mb={1}
                      bgGradient={isPinned 
                        ? "linear(to-r, green.600, green.400)"
                        : "linear(to-r, blue.600, blue.400)"
                      }
                      bgClip="text"
                      fontWeight="bold"
                      cursor="pointer"
                      onClick={(e) => handleShowFullNote(note, e)}
                      textDecoration="underline"
                      _hover={{ opacity: 0.8 }}
                    >
                      {note.title}
                    </Heading>
                    <HStack 
                      spacing={1} 
                      opacity="0.7" 
                      _groupHover={{ opacity: 1 }} 
                      transition="opacity 0.2s"
                    >
                      <Tooltip label={isPinned ? "Unpin note" : "Pin note"} placement="top">
                        <IconButton
                          aria-label={isPinned ? "Unpin note" : "Pin note"}
                          icon={<FaThumbtack />}
                          size="sm"
                          variant="ghost"
                          colorScheme={isPinned ? "green" : "gray"}
                          transform={isPinned ? "rotate(0deg)" : "rotate(45deg)"}
                          onClick={(e) => togglePinNote(note, e)}
                        />
                      </Tooltip>
                      <Tooltip label="Delete note" placement="top">
                        <IconButton
                          aria-label="Delete note"
                          icon={<FaTrash />}
                          size="sm"
                          variant="ghost"
                          colorScheme="red"
                          onClick={(e) => {
                            e.stopPropagation();
                            handleDeleteNote(note);
                          }}
                        />
                      </Tooltip>
                    </HStack>
                  </Flex>
                  
                  {/* Note content preview */}
                  <Box 
                    mb={4} 
                    flex="1" 
                    overflow="auto"
                    overflowX="hidden"
                    bg="yellow.50"
                    _dark={{ bg: "yellow.900" }}
                    borderRadius="md"
                    p={3}
                    height="280px"
                    cursor="copy"
                    position="relative"
                    onClick={(e) => {
                      e.stopPropagation();
                      isCode 
                        ? handleCopyCodeBlock(note.content, e) 
                        : handleCopyContent(note, e);
                    }}
                    _hover={{
                      bg: "yellow.100",
                      _dark: { bg: "yellow.800" }
                    }}
                    css={{
                      '&::-webkit-scrollbar': {
                        width: '4px',
                      },
                      '&::-webkit-scrollbar-track': {
                        width: '6px',
                        background: 'transparent',
                      },
                      '&::-webkit-scrollbar-thumb': {
                        background: 'gray.200',
                        borderRadius: '24px',
                      },
                      '&:hover::-webkit-scrollbar-thumb': {
                        background: 'gray.400',
                      },
                      scrollbarWidth: 'thin',
                      scrollbarColor: 'gray.200 transparent',
                    }}
                    _groupHover={{
                      '&::-webkit-scrollbar-thumb': {
                        background: 'gray.400',
                      },
                    }}
                  >
                    {isCode ? (
                      <Box 
                        as="pre"
                        fontSize="sm"
                        fontFamily="monospace"
                        whiteSpace="pre-wrap"
                        sx={{
                          code: {
                            display: 'block',
                            whiteSpace: 'pre-wrap',
                          }
                        }}
                      >
                        {formatContent(note.content)}
                      </Box>
                    ) : (
                      <Box
                        fontSize="sm"
                        color="gray.600"
                        _dark={{ color: "gray.300" }}
                        lineHeight="1.6"
                        letterSpacing="0.01em"
                        className="note-content"
                        sx={{
                          p: { 
                            marginTop: '0.5em',
                            marginBottom: '0.5em',
                          },
                          ul: { 
                            paddingLeft: '1.5em',
                            marginTop: '0.5em',
                            marginBottom: '0.5em',
                          },
                          ol: { 
                            paddingLeft: '1.5em',
                            marginTop: '0.5em',
                            marginBottom: '0.5em',
                          },
                          li: { 
                            marginBottom: '0.25em',
                          },
                          h1: {
                            fontSize: '1.2em',
                            fontWeight: 'bold',
                            marginTop: '0.5em',
                            marginBottom: '0.5em',
                          },
                          h2: {
                            fontSize: '1.1em',
                            fontWeight: 'bold',
                            marginTop: '0.5em',
                            marginBottom: '0.5em',
                          },
                          h3: {
                            fontSize: '1em',
                            fontWeight: 'bold',
                            marginTop: '0.5em',
                            marginBottom: '0.5em',
                          },
                          pre: {
                            backgroundColor: 'gray.100',
                            _dark: { backgroundColor: 'gray.700' },
                            padding: '0.5em',
                            borderRadius: '0.25em',
                            overflow: 'auto',
                          },
                          blockquote: {
                            borderLeftWidth: '2px',
                            borderLeftColor: 'gray.300',
                            _dark: { borderLeftColor: 'gray.600' },
                            paddingLeft: '0.75em',
                            fontStyle: 'italic',
                          },
                          img: {
                            maxWidth: '100%',
                            height: 'auto',
                          }
                        }}
                        dangerouslySetInnerHTML={{ __html: note.content }}
                      />
                    )}
                  </Box>
                  
                  {/* Footer with metadata */}
                  <Flex 
                    justify="space-between" 
                    align="center" 
                    pt={3}
                    mt="auto"
                    borderTopWidth="1px"
                    borderTopColor={borderColor}
                    borderTopStyle="dashed"
                    position="relative"
                  >
                    <Text 
                      fontSize="xs" 
                      color="gray.500" 
                      fontStyle="italic"
                    >
                      {formatDate(note.updatedAt)}
                    </Text>
                    <HStack spacing={2}>
                      {isPinned && (
                        <Tooltip label="Pinned note" placement="top">
                          <Badge 
                            colorScheme="green" 
                            variant="subtle" 
                            p={1.5} 
                            borderRadius="full"
                            display="flex"
                            alignItems="center"
                            justifyContent="center"
                          >
                            <FaThumbtack size={10} />
                          </Badge>
                        </Tooltip>
                      )}
                      {note.hasImages && (
                        <Tooltip label="Contains images" placement="top">
                          <Badge 
                            colorScheme="green" 
                            variant="subtle" 
                            p={1.5} 
                            borderRadius="full"
                            display="flex"
                            alignItems="center"
                            justifyContent="center"
                          >
                            <FaImage size={10} />
                          </Badge>
                        </Tooltip>
                      )}
                      {note.hasCodeSnippets && (
                        <Tooltip label="Contains code snippets" placement="top">
                          <Badge 
                            colorScheme="purple" 
                            variant="subtle" 
                            p={1.5} 
                            borderRadius="full"
                            display="flex"
                            alignItems="center"
                            justifyContent="center"
                          >
                            <FaCode size={10} />
                          </Badge>
                        </Tooltip>
                      )}
                    </HStack>
                    
                    {/* Edit button in bottom right */}
                    <Box position="absolute" right="0" bottom="-12px">
                      <Tooltip label="Edit note" placement="top">
                        <IconButton
                          aria-label="Edit note"
                          icon={<FaEdit />}
                          size="sm"
                          variant="solid"
                          colorScheme="blue"
                          onClick={(e) => {
                            e.stopPropagation();
                            handleEditNote(note);
                          }}
                        />
                      </Tooltip>
                    </Box>
                  </Flex>
                </Flex>
              </Box>
            );
          })}
        </SimpleGrid>
      ) : (
        <Box 
          textAlign="center" 
          p={10}
          py={16} 
          borderWidth="1px" 
          borderRadius="xl"
          borderColor={borderColor}
          bg={noteBgColor}
          boxShadow="md"
        >
          <Text fontSize="lg">
            {searchQuery ? 'No notes found. Try a different search term.' : 'Create your first note!'}
          </Text>
        </Box>
      )}

      {/* Note Editor Modal */}
      <Modal isOpen={isOpen} onClose={onClose} size="xl">
        <ModalOverlay />
        <ModalContent maxW={{ base: "90%", md: "800px" }}>
          <ModalHeader>
            {selectedNote ? 'Edit Note' : 'Create Note'}
          </ModalHeader>
          <ModalCloseButton />
          
          <ModalBody>
            <FormControl mb={4}>
              <FormLabel>Title</FormLabel>
              <Input 
                value={title} 
                onChange={(e) => setTitle(e.target.value)} 
                placeholder="Note title"
              />
            </FormControl>
            
            <FormControl>
              <FormLabel>Content</FormLabel>
              <Box border="1px" borderColor={borderColor} borderRadius="md">
                <div>
                  <ReactQuill
                    ref={quillRef}
                    theme="snow"
                    value={content}
                    onChange={setContent}
                    placeholder="Write your notes here... Paste images or use toolbar to add code snippets"
                    style={{ height: '300px' }}
                  />
                </div>
              </Box>
              <Text fontSize="sm" mt={2} color="gray.500">
                Tip: You can paste images directly into the editor (Ctrl+V)
              </Text>
            </FormControl>
          </ModalBody>

          <ModalFooter>
            <Button 
              leftIcon={<FaSave />}
              colorScheme="blue"
              onClick={handleSaveNote}
              isLoading={isSaving}
              loadingText="Saving"
              mr={3}
            >
              Save
            </Button>
            <Button onClick={onClose}>Cancel</Button>
          </ModalFooter>
        </ModalContent>
      </Modal>

      {/* Delete Confirmation AlertDialog */}
      <AlertDialog
        isOpen={isDeleteAlertOpen}
        leastDestructiveRef={cancelRef}
        onClose={onDeleteAlertClose}
      >
        <AlertDialogOverlay>
          <AlertDialogContent>
            <AlertDialogHeader fontSize="lg" fontWeight="bold">
              Delete Note
            </AlertDialogHeader>

            <AlertDialogBody>
              Are you sure you want to delete "{noteToDelete?.title}"? This action cannot be undone.
            </AlertDialogBody>

            <AlertDialogFooter>
              <Button ref={cancelRef} onClick={onDeleteAlertClose}>
                Cancel
              </Button>
              <Button colorScheme="red" onClick={confirmDeleteNote} ml={3}>
                Delete
              </Button>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialogOverlay>
      </AlertDialog>

      {/* Full Note Viewer Modal */}
      <Modal isOpen={isFullViewOpen} onClose={onFullViewClose} size="xl" scrollBehavior="inside">
        <ModalOverlay />
        <ModalContent maxW={{ base: "95%", md: "90%", lg: "80%" }} maxH="90vh">
          <ModalHeader 
            bgGradient={(fullViewNote?.pinned) 
              ? "linear(to-r, green.600, green.400)"
              : "linear(to-r, blue.600, blue.400)"
            }
            bgClip="text"
          >
            {fullViewNote?.title}
          </ModalHeader>
          <ModalCloseButton />
          
          <ModalBody>
            {fullViewNote?.hasCodeSnippets ? (
              <Box 
                as="pre"
                fontSize="sm"
                fontFamily="monospace"
                whiteSpace="pre-wrap"
                bg="gray.50"
                _dark={{ bg: "gray.800" }}
                p={4}
                borderRadius="md"
                overflowX="auto"
              >
                {formatContent(fullViewNote?.content || '')}
              </Box>
            ) : (
              <Box
                p={4}
                borderRadius="md"
                bg="white"
                _dark={{ bg: "gray.800" }}
                className="note-content-full"
                sx={{
                  p: { 
                    marginTop: '0.75em',
                    marginBottom: '0.75em',
                  },
                  ul: { 
                    paddingLeft: '2em',
                    marginTop: '0.75em',
                    marginBottom: '0.75em',
                  },
                  ol: { 
                    paddingLeft: '2em',
                    marginTop: '0.75em',
                    marginBottom: '0.75em',
                  },
                  li: { 
                    marginBottom: '0.5em',
                  },
                  h1: {
                    fontSize: '1.5em',
                    fontWeight: 'bold',
                    marginTop: '1em',
                    marginBottom: '0.5em',
                  },
                  h2: {
                    fontSize: '1.3em',
                    fontWeight: 'bold',
                    marginTop: '0.75em',
                    marginBottom: '0.5em',
                  },
                  h3: {
                    fontSize: '1.1em',
                    fontWeight: 'bold',
                    marginTop: '0.75em',
                    marginBottom: '0.5em',
                  },
                  pre: {
                    backgroundColor: 'gray.100',
                    _dark: { backgroundColor: 'gray.700' },
                    padding: '1em',
                    borderRadius: '0.25em',
                    overflowX: 'auto',
                    marginTop: '0.75em',
                    marginBottom: '0.75em',
                  },
                  code: {
                    fontFamily: 'monospace',
                    fontSize: '0.9em',
                    padding: '0.2em 0.4em',
                    borderRadius: '0.25em',
                    backgroundColor: 'gray.100',
                    _dark: { backgroundColor: 'gray.700' },
                  },
                  blockquote: {
                    borderLeftWidth: '4px',
                    borderLeftColor: 'gray.300',
                    _dark: { borderLeftColor: 'gray.600' },
                    paddingLeft: '1em',
                    fontStyle: 'italic',
                    marginTop: '0.75em',
                    marginBottom: '0.75em',
                  },
                  img: {
                    maxWidth: '100%',
                    height: 'auto',
                    margin: '0.75em 0',
                    borderRadius: '0.25em',
                  },
                  a: {
                    color: 'blue.500',
                    _dark: { color: 'blue.300' },
                    textDecoration: 'underline',
                  }
                }}
                dangerouslySetInnerHTML={{ __html: fullViewNote?.content || '' }}
              />
            )}
            
            <Flex justify="space-between" mt={6} color="gray.500" fontSize="sm">
              <Text>Created: {fullViewNote ? formatDate(fullViewNote.createdAt) : ''}</Text>
              <Text>Updated: {fullViewNote ? formatDate(fullViewNote.updatedAt) : ''}</Text>
            </Flex>
          </ModalBody>

          <ModalFooter>
            <Button
              leftIcon={<FaEdit />}
              colorScheme="blue"
              mr={3}
              onClick={() => {
                onFullViewClose();
                if (fullViewNote) {
                  handleEditNote(fullViewNote);
                }
              }}
            >
              Edit
            </Button>
            <Button
              leftIcon={<FaCopy />}
              colorScheme="green"
              mr={3}
              onClick={() => {
                if (fullViewNote) {
                  // Extract text content while preserving line breaks
                  const tempDiv = document.createElement('div');
                  tempDiv.innerHTML = fullViewNote.content;
                  
                  // Process the HTML to maintain basic formatting
                  const processNode = (node: Node): string => {
                    // Text node - return its content
                    if (node.nodeType === Node.TEXT_NODE) {
                      return node.textContent || '';
                    }
                    
                    // Handle various HTML elements that affect formatting
                    if (node.nodeType === Node.ELEMENT_NODE) {
                      const element = node as Element;
                      const tagName = element.tagName.toLowerCase();
                      
                      // Add line breaks before and after block elements
                      const isBlockElement = ['div', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote', 'pre'].includes(tagName);
                      
                      let result = isBlockElement ? '\n' : '';
                      
                      // Process all child nodes
                      for (let i = 0; i < node.childNodes.length; i++) {
                        result += processNode(node.childNodes[i]);
                      }
                      
                      // Special handling for specific tags
                      if (tagName === 'br') result += '\n';
                      if (tagName === 'li') result += '\n';
                      if (tagName === 'pre' || tagName === 'code') result = result.trimRight(); // don't add extra linebreaks
                      
                      // Add line break after block elements
                      if (isBlockElement) result += '\n';
                      
                      return result;
                    }
                    
                    // Process all other node types recursively
                    let result = '';
                    for (let i = 0; i < node.childNodes.length; i++) {
                      result += processNode(node.childNodes[i]);
                    }
                    return result;
                  };
                  
                  const formattedText = processNode(tempDiv).trim();
                  
                  // Copy the formatted text
                  const textArea = document.createElement('textarea');
                  textArea.value = formattedText;
                  document.body.appendChild(textArea);
                  textArea.select();
                  
                  try {
                    document.execCommand('copy');
                    toast({
                      title: 'Content copied to clipboard with formatting preserved',
                      status: 'success',
                      duration: 2000,
                      isClosable: true,
                    });
                  } catch (err) {
                    console.error('Failed to copy text: ', err);
                    toast({
                      title: 'Failed to copy text',
                      status: 'error',
                      duration: 2000,
                      isClosable: true,
                    });
                  } finally {
                    document.body.removeChild(textArea);
                  }
                }
              }}
            >
              Copy Text
            </Button>
            <Button onClick={onFullViewClose}>Close</Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </Container>
  );
};

export default NotesPage;